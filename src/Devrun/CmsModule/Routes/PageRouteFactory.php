<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2020
 *
 * @file    PageRouteFactory.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Routes;

use Devrun;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Devrun\CmsModule\Facades\DomainFacade;
use Devrun\CmsModule\Facades\PackageFacade;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\CmsModule\Repositories\RouteTranslationRepository;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\Translator;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\Strings;

/**
 * Class PageRouteFactory
 * @package Devrun\CmsModule\Routes
 */
class PageRouteFactory implements Subscriber
{

    /** @var Cache */
    private $cache;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var RouteTranslationRepository */
    private $routeTranslationRepository;

    /** @var ITranslator|Translator */
    private $translator;

    /** @var string */
    protected $locales;

    /** @var string */
    protected $defaultLocale = 'cs';

    /** @var string */
    protected $defaultDomain;

    /** @var bool */
    private $useCache = true;


    /**
     * PageRouteFactory constructor.
     *
     * @param string|null $defaultDomain
     * @param IStorage $storage
     * @param RouteRepository $routeRepository
     * @param RouteTranslationRepository $routeTranslationRepository
     * @param ITranslator $translator
     */
    public function __construct(IStorage $storage, RouteRepository $routeRepository, RouteTranslationRepository $routeTranslationRepository, ITranslator $translator, ?string $defaultDomain)
    {
        $this->cache                      = new Cache($storage, 'routes');
        $this->translator                 = $translator;
        $this->defaultDomain              = $defaultDomain;
        $this->routeRepository            = $routeRepository;
        $this->routeTranslationRepository = $routeTranslationRepository;

        $this->initLocales();
    }

    /**
     * init locales from translator
     * locales [cs|sk|en ...]
     * defaultLocale [cs]
     */
    private function initLocales(): void
    {
        $availableLocalesArray = ($availableLocales = $this->translator->getAvailableLocales())
            ? $availableLocales
            : [$this->defaultLocale];

        $this->locales = implode('|', array_unique(preg_replace("/^(\w{2})_(.*)$/m", "$1", $availableLocalesArray)));

        if ($defaultLocale = $this->translator->getDefaultLocale()) {
            $this->defaultLocale = $defaultLocale;
        }
    }


    /**
     * @return IRouter
     */
    public function create()
    {
        $router = new RouteList();

        /*
         * add cms route
         */
        $adminRouter   = new RouteList('Cms');
        $adminRouter[] = new Route("[<module>-]admin[-package-<package>]/[<locale={$this->defaultLocale} {$this->locales}>/]<presenter>/<action>[/<id>]", array(
            'presenter' => 'Default',
            'action'    => 'default',
        ));

        $router[] = $adminRouter;

        $domainMask = $this->defaultDomain ? '//<domain .*>/' : null;
        $router[]   = new Route("{$domainMask}[<locale={$this->defaultLocale} {$this->locales}>/][<slug .+>][/<id \\d+>]", [
            'slug' => '',
            'module' => null,
            null => [
                Route::FILTER_IN  => function (array $parameters) {

                    $key = $this->defaultDomain
                        ? [$this->defaultDomain, $parameters['slug'], $parameters['locale']]
                        : [$parameters['slug'], $parameters['locale']];

                    if ($data = $this->cache->load($key)) {
                        return $this->modifyMatchRequest($data[0], $data[1], $data[2], $data[3], $data[4], $parameters);
                    }

                    /** @var RouteTranslationEntity $routeTranslation */
                    $query = $this->routeTranslationRepository
                        ->createQueryBuilder('t')
                        ->addSelect('t')
                        ->addSelect('r')
                        ->addSelect('page')
                        ->addSelect('package')
                        ->join('t.translatable', 'r')
                        ->leftJoin('t.domain', 'd')
                        ->leftJoin('r.page', 'page')
                        ->leftJoin('r.package', 'package')
                        ->andWhere('t.locale = :locale OR t.locale = :defaultLocale')
                        ->setMaxResults(1)
                        ->setParameters([
                            'url'           => $parameters['slug'],
                            'locale'        => $parameters['locale'],
                            'defaultLocale' => $this->defaultLocale,
                        ]);

                    $generateDomain = $this->isGenerateDomain($parameters);
                    if ($generateDomain) {
                        $query
                            ->andWhere('(t.domainUrl = :url AND d.valid = true AND d.name = :domain) OR (t.url = :url AND (t.domain IS NULL OR d.valid = false))')
                            ->setParameter('domain', $parameters['domain']);

                    } else {
                        $query->andWhere('t.url = :url');
                    }

                    if (!$routeTranslation = $query->getQuery()->getOneOrNullResult()) {
                        return null;
                    }

                    /** @var RouteEntity $route */
                    $route = $routeTranslation->getTranslatable();

                    $package   = $route->hasPackage() ? $route->getPackage() : null;
                    $packageId = $route->hasPackage() ? $route->getPackage()->getId() : null;

                    if ($this->useCache) {
                        $this->cache->save($key, array(
                            $route->id,
                            $route->getPage()->id,
                            $route->getUri(),
                            $packageId,
                            $route->getParams()),
                            array(
                                Cache::TAGS => array(RouteEntity::CACHE),
                            ));
                    }

                    return $this->modifyMatchRequest($route, $route->getPage(), $route->getUri(), $package, $route->getParams(), $parameters);
                },

                Route::FILTER_OUT => function (array $parameters) {

                    /*
                     * filters where not continue
                     */
                    if (Strings::startsWith($parameters['presenter'], "Cms:")) {
//                        return null;
                    }

                    /*
                     * pokud se jedná o link, který teprve generujeme (nemá slug) [$this->link | {link}] smažeme z parametrů route a page,
                     * tyto hodnoty jsou persistentní, naopak necháme persistentní package, tu můžeme modifikovat [{link Homepage: package=>2}]
                     */
                    if (!isset($parameters['slug'])) {
                        unset($parameters['route']);
                        unset($parameters['page']);
                        // unset($parameters['package']);
                    }

                    $key = $parameters;

                    unset($key['_route']);
                    unset($key['_page']);
                    unset($key['_package']);

                    if ($data = $this->cache->load($key)) {
                        return $data;
                    }

                    /** @var RouteEntity|null $routeEntity */
                    $route = $routeEntity = null;

                    if (isset($parameters['_route'])) {
                        $routeEntity = $parameters['_route'];

                    } elseif (isset($parameters['route'])) {
                        $route = $parameters['route'];

                    } else {

                        /*
                         * pokusíme se sestavit url bez routy, to provedeme pomocí presenter requestu, balíčku a případných parametrů
                         */
                        $uri  = ":" . $parameters['presenter'] . ":" . $parameters['action'];
                        $id   = $parameters['id'] ?? null;
                        $lang = $parameters['locale'] ?? null;
                        $pId  = $parameters['package'] ?? null;

                        $query = $this->routeRepository
                            ->createQueryBuilder('e')
                            ->addSelect('t')
                            ->addSelect('p')
                            ->leftJoin('e.translations', 't')
                            ->leftJoin('e.package', 'p')
                            ->where('e.uri = :uri')->setParameter('uri', $uri)
                            ->setMaxResults(1);

                        if ($lang) {
                            $query->andWhere('t.locale = :locale')->setParameter('locale', $lang);
                        }

                        if ($pId === null) {
                            $query->andWhere('e.package IS NULL OR p.name = :packageName')->setParameter('packageName', 'Default');;

                        } else {
                            $query->andWhere('e.package = :package')->setParameter('package', $pId);
                        }

                        if ($id) {
                            $query->andWhere('e.params = :params OR e.params = :range')
                                  ->setParameter('params', json_encode(['id' => $id], JSON_NUMERIC_CHECK))
                                  ->setParameter('range', json_encode(['id' => '?']));
                        }

                        $routeEntity = $query->getQuery()->getOneOrNullResult();
                    }

                    if (!$routeEntity && $route) {
                        $routeEntity = $this->routeRepository
                            ->createQueryBuilder('e')
                            ->addSelect('t')
                            ->leftJoin('e.translations', 't')
                            ->where('e.id = :id')->setParameter('id', $route)
                            ->setMaxResults(1)
                            ->getQuery()
                            ->getOneOrNullResult();
                    }
                    if (!$routeEntity) {
                        return null;
                    }

                    $request = $this->modifyConstructRequest($routeEntity, $parameters);

                    if ($this->useCache) {
                        $this->cache->save($key, $request, array(
                            Cache::TAGS => array(RouteEntity::CACHE),
                        ));
                    }

                    return $request;
                },
            ],
        ]);

        return $router;
    }


    /**
     * modify request by route, page, package + route parameters
     *
     * @param $route
     * @param $page
     * @param $routeUri
     * @param $package
     * @param $routeParameters
     * @param $parameters
     * @return array
     */
    protected function modifyMatchRequest($route, $page, $routeUri, $package, $routeParameters, $parameters): array
    {
        if (is_object($route)) {
            $parameters['route'] = $route->id;
            $parameters['_route'] = $route;
        } else {
            $parameters['route'] = $route;
        }

        if (is_object($page)) {
            $parameters['page'] = $page->id;
            $parameters['_page'] = $page;
        } else {
            $parameters['page'] = $page;
        }

        if (is_object($package)) {
            $parameters['package'] = $package->id;
            $parameters['_package'] = $package;
        } else {
            $parameters['package'] = $package;
        }

        if (isset($routeParameters['id']) && $routeParameters['id'] == '?') {
            unset($routeParameters['id']);
        }

        $parameters = $routeParameters + $parameters;
        $type = explode(':', ltrim($routeUri, ':'));

        $parameters['action'] = $type[count($type) - 1];

        unset($type[count($type) - 1]);
        if (count($type) > 1) {
            $parameters['module'] = $type[0];
            unset($type[0]);
        }

        $presenter = join(':', $type);
        $parameters['presenter'] = $presenter;
        return $parameters;
    }


    /**
     * @param array $parameters
     * @return bool
     */
    protected function isGenerateDomain(array $parameters): bool
    {
        return $parameters['generateDomain'] ?? ($this->defaultDomain == true);
    }


    /**
     * Modify construct request by page
     *
     * @param RouteEntity $route
     * @param array $parameters
     * @return array
     */
    protected function modifyConstructRequest( RouteEntity $route, array $parameters): array
    {
        $locale    = $parameters['locale'] ?? $this->defaultLocale;
        $useDomain = $this->isGenerateDomain($parameters);
        $slug      = $route->translate($locale)->getUrl();
        $domain    = $this->defaultDomain;

        if ($useDomain && ($routeDomain = $route->getDomain())) {
            if ($routeDomain->isValid()) {
                $domain = $routeDomain->getName();
                $slug = $route->translate($locale)->getDomainUrl();
            }
        }

        if ($route->getParams() != '{"id":"?"}' ) {
            unset($parameters['id']);
        }

        $request = ['slug' => $slug] + $parameters;
        if ($domain) $request['domain'] = $domain;
        unset($request['_route']);
        unset($request['_page']);
        unset($request['_package']);
        unset($request['route']);
        unset($request['page']);
        unset($request['package']);

        // slug je nepovinný parametr, i přesto že je prázdný, je třeba jej smazat
        if (($parameters['slug'] ?? '') === '') unset($request['slug']);
        unset($request['module'], $request['presenter'], $request['action']);

        return $request;
    }


    /**
     * @return string
     */
    public function getLocales(): string
    {
        return $this->locales;
    }

    /**
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * @return string
     */
    public function getDefaultDomain(): string
    {
        return $this->defaultDomain;
    }


    public function cleanCache()
    {
        if ($this->useCache) {
            $this->cache->clean([
                Cache::TAGS => array(RouteEntity::CACHE),
            ]);
        }
    }


    /**
     * @return array for subscribe events
     */
    function getSubscribedEvents()
    {
        return [
            PackageFacade::EVENT_COPY_PACKAGE        => 'cleanCache',
            PackageFacade::EVENT_CHANGE_PACKAGE      => 'cleanCache',
            PackageFacade::EVENT_REMOVE_PACKAGE      => 'cleanCache',
            DomainFacade::class . '::onChangeDomain' => 'cleanCache',
        ];
    }
}
