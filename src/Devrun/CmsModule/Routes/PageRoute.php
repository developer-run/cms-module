<?php
/**
 * This file is part of cms
 * Copyright (c) 2019
 *
 * @file    PageRoute.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Routes;

use Devrun;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Devrun\CmsModule\Facades\PackageFacade;
use Devrun\CmsModule\Repositories\RouteRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette;
use Nette\Application;
use Nette\Application\Routers\Route;


/**
 * @deprecated use PageRouteFactory instead [nette 3]
 *
 * Class PageRoute
 * @package Devrun\CmsModule\Routes
 */
class PageRoute extends Route implements Subscriber
{
    const DEFAULT_MODULE = 'Cms';
    const DEFAULT_PACKAGE = null;
    const DEFAULT_PRESENTER = 'Homepage';
    const DEFAULT_ACTION = 'default';

    /** @var array */
    protected $languages;

    /** @var string */
    protected $defaultLanguage;

    /** @var bool */
    protected $_defaultLang = FALSE;

    /** @var string */
    protected $defaultDomain;

    /** @var Nette\Caching\Cache */
    private $cache;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var EntityManager */
    private $em;

    private $useCache = true;

    /**
     * PageRoute constructor.
     * <slug .+>[/<module qwertzuiop>/<presenter qwertzuiop>] . (count($this->languages) > 1 && strpos($prefix, '<lang>') === FALSE ? '?lang=<lang>' : '')
     *
     * @param                        $prefix
     * @param array                  $parameters
     * @param int                    $defaultLocale
     * @param                        $availableLocales
     * @param null                   $defaultDomain
     * @param Nette\Caching\IStorage $storage
     * @param EntityManager          $entityManager
     * @param RouteRepository        $routeRepository
     * @param bool                   $oneWay
     */
    public function __construct($prefix, $parameters, $defaultLocale, $availableLocales, $defaultDomain = null, Nette\Caching\IStorage $storage, EntityManager $entityManager, RouteRepository $routeRepository, $oneWay = false)
    {
        $this->languages = $availableLocales;
        $this->cache = new Nette\Caching\Cache($storage, 'routes');
        $this->routeRepository = $routeRepository;
        $this->em = $entityManager;

        $this->defaultLanguage = $defaultLocale;
        $this->defaultDomain = $defaultDomain;

        $availLocales = implode("|", $availableLocales);
        $domainMask   = $defaultDomain ? '//<domain .*>/' : null;

        parent::__construct($prefix . "{$domainMask}[<locale=$defaultLocale $availLocales>/]<slug .+>[/<module qwertzuiop>/<presenter qwertzuiop>][/<id \\d+>]", $parameters + array(
//                'presenter' => self::DEFAULT_PRESENTER,
                'domain' => $this->defaultDomain,
                'presenter' => array(
                    self::VALUE => self::DEFAULT_PRESENTER,
//                    self::FILTER_IN => function($q) {
//                        Debugger::barDump($q);
//                        return $q;
//                    },
//                    self::FILTER_OUT => function($q) {
//                        Debugger::barDump($q);
////                        dump($q);
//                        return $q;
//                    },
                ),
                'module' => self::DEFAULT_MODULE,
//                'module' => null,
//                'package' => self::DEFAULT_PACKAGE,
//                'package' => array(
//                    self::VALUE => self::DEFAULT_PACKAGE,
//                    self::FILTER_IN => function($q) {
//                        dump($q);
//                    },
//                    self::FILTER_OUT => function($q) {
//                        dump($q);
//                    },
//                ),
                'action' => self::DEFAULT_ACTION,
//                'id' => NULL,
                'locale' => $defaultLocale,
                'slug' => array(
                    self::VALUE => '',
//                    self::FILTER_IN => function($q) {
//                        Debugger::barDump($q);
//                        return $q;
//                    },
//                    self::FILTER_OUT => function($q) {
//                        Debugger::barDump($q);
////                        dump($q);
//                        return $q;
//                    },
                )
            ), $oneWay ? Route::ONE_WAY : NULL);

    }

    public function match(Nette\Http\IRequest $httpRequest)
    {
        if (($request = parent::match($httpRequest)) === NULL || !array_key_exists('slug', $request->parameters)) {
            return NULL;
        }

        $parameters = $request->parameters;
        $generateDomain = $request->getParameter('generateDomain') ?? true;


        if (!$this->_defaultLang && count($this->languages) > 1) {
            if (!isset($parameters['locale'])) {
                $parameters['locale'] = $this->defaultLanguage;
            }

            if ($parameters['locale'] !== $this->defaultLanguage) {
                //$this->container->cms->pageListener->setLocale($parameters['locale']);
            }

            $this->_defaultLang = TRUE;
        }

        $key = array($httpRequest->getUrl()->getAbsoluteUrl(), $parameters['locale']);
        $data = $this->cache->load($key);
        if ($data) {
            return $this->modifyMatchRequest($request, $data[0], $data[1], $data[2], $data[3], $data[4], $parameters);
        }

        /** @var RouteTranslationEntity $routeTranslation */
        $query = $this->em->createQueryBuilder()
            ->addSelect('t')
            ->addSelect('r')
            ->from(RouteTranslationEntity::class, 't')
            ->join('t.translatable', 'r')
            ->leftJoin('t.domain', 'd')
            ->andWhere('t.locale = :locale OR t.locale = :defaultLocale')
            ->setParameters([
                'url' => $parameters['slug'],
                'locale' => $parameters['locale'],
                'defaultLocale' => $this->defaultLanguage,
            ]);

        if ($generateDomain) {
            $query
                ->andWhere('(t.domainUrl = :url AND d.valid = true AND d.name = :domain) OR (t.url = :url AND (t.domain IS NULL OR d.valid = false))')
                ->setParameter('domain', $parameters['domain']);

        } else {
            $query->andWhere('t.url = :url');
        }

        $routeTranslation = $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$routeTranslation) {
            return null;
        }

        /** @var RouteEntity $route */
        $route = $routeTranslation->getTranslatable();

        if ($this->useCache) {
            $this->cache->save($key, array(
                $route->id,
                $route->getPage()->id,
                $route->getUri(),
                $route->getPackage() ? $route->getPackage()->getId() : null,
                $route->getParams()),
                array(
                    Nette\Caching\Cache::TAGS => array(RouteEntity::CACHE),
                ));
        }

        return $this->modifyMatchRequest($request, $route, $route->getPage(), $route->getUri(), $route->getPackage(), $route->getParams(), $parameters);
    }


    /**
     * modify request by route, page, package + route parameters
     *
     * @param Application\Request $appRequest
     * @param $route
     * @param $page
     * @param $routeType
     * @param $package
     * @param $routeParameters
     * @param $parameters
     * @return Application\Request
     */
    protected function modifyMatchRequest(\Nette\Application\Request $appRequest, $route, $page, $routeType, $package, $routeParameters, $parameters)
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
        $type = explode(':', $routeType);
        $parameters['action'] = $type[count($type) - 1];

        unset($type[count($type) - 1]);
        $presenter = join(':', $type);
        $presenter = Nette\Utils\Strings::replace($presenter, "/^:/");

        $appRequest->setParameters($parameters);
        $appRequest->setPresenterName($presenter);

        return $appRequest;
    }



    public function constructUrl(Application\Request $appRequest, Nette\Http\Url $refUrl)
    {
        $parameters = $appRequest->getParameters();
        $key        = ['presenter' => $appRequest->getPresenterName()] + (array)$parameters;

        unset($key['_route']);
        unset($key['_page']);
        unset($key['_package']);

        /*
         * compatibility, can delete this yet
         */
        if (isset($key['route']) && is_object($key['route'])) {
            $key['route'] = $key['route'] instanceof RouteEntity ? $key['route']->id : $key['route']->route->id;
        }
        if (isset($key['page']) && is_object($key['page']) && $key['page'] instanceof Devrun\CmsModule\Entities\PageEntity) {
            $key['page'] = $key['page']->id;
        }
        if (isset($key['package']) && is_object($key['package']) && $key['package'] instanceof Devrun\CmsModule\Entities\PackageEntity) {
            $key['package'] = $key['package']->id;
        }

        if ($data = $this->cache->load($key)) {
            return $data;
        }

        /** @var RouteEntity|null $routeEntity */
        $routeEntity = null;

        if (isset($parameters['route'])) {
            $route = is_object($parameters['route'])
                ? ($parameters['route'] instanceof RouteEntity ? $parameters['route']->route->id : $parameters['route']->id)
                : $parameters['route'];

        } elseif (isset($parameters['_route'])) {
            $routeEntity = $parameters['_route'];
            $route = $routeEntity->id;

        } else {
            // pokusíme se sestavit url bez routy, to provedeme pomocí presenter requestu, balíčku a případných parametrů

            $uri       = ":" . $appRequest->getPresenterName() . ":" . $parameters['action'];
            $id        = $parameters['id'] ?? null;
            $lang      = $parameters['locale'] ?? null;
            $packageId = $parameters['package'] ?? null;

            $query = $this->routeRepository->createQueryBuilder('e')
                ->addSelect('t')
                ->addSelect('p')
                ->addSelect('u')
                ->join('e.translations', 't')
                ->join('e.package', 'p')
                ->leftJoin('p.user', 'u')
                ->where('e.uri = :uri')->setParameter('uri', $uri)
                ->setMaxResults(1);

            if ($packageId === null) {
                $query->andWhere('e.package IS NULL OR p.name = :packageName')->setParameter('packageName', 'default');;

            } else {
                $query->andWhere('e.package = :package')->setParameter('package', $packageId);
            }

            if ($id) {
                $query->andWhere('e.params = :params OR e.params = :range')
                    ->setParameter('params', json_encode(['id' => $id], JSON_NUMERIC_CHECK))
                    ->setParameter('range', json_encode(['id' => '?']));
            }

            $routeEntity = $query
                ->getQuery()
                ->getOneOrNullResult();

            if (!$routeEntity) {
                return null;
            }

            $this->modifyConstructRequest($appRequest, $routeEntity, $parameters);
            $data = parent::constructUrl($appRequest, $refUrl);

            if ($this->useCache) {
                $this->cache->save($key, $data, array(
                    Nette\Caching\Cache::TAGS => array(RouteEntity::CACHE),
                ));
            }

            return $data;
        }

        unset($parameters['_route']);
        unset($parameters['_page']);
        unset($parameters['_package']);
        unset($parameters['route']);
        unset($parameters['page']);
        unset($parameters['package']);

        if (!$routeEntity) {
            $routeEntity = $this->routeRepository->find($route);
        }

        $this->modifyConstructRequest($appRequest, $routeEntity, $parameters);
        $data = parent::constructUrl($appRequest, $refUrl);

        if ($this->useCache) {
            $this->cache->save($key, $data, array(
                Nette\Caching\Cache::TAGS => array(RouteEntity::CACHE),
            ));
        }

        return $data;
    }



    /**
     * Modify request by page
     *
     * @param Nette\Application\Request $request
     * @param RouteEntity $route
     * @param $parameters
     * @return \Nette\Application\Request
     */
    protected function modifyConstructRequest(Application\Request $request, RouteEntity $route, $parameters)
    {
        $defaults  = $this->getDefaults();
        $locale    = $parameters['locale'] ?? $this->defaultLanguage;
        $useDomain = $parameters['generateDomain'] ?? true;
        $slug      = $route->translate($locale)->getUrl();
        $domain    = $this->defaultDomain;

        if ($useDomain && ($routeDomain = $route->getDomain())) {
            if ($routeDomain->isValid()) {
                $domain = $routeDomain->getName();
                $slug = $route->translate($locale)->getDomainUrl();
            }
        }

        if ($route->getParams($raw = true) != '{"id":"?"}' ) {
            unset($parameters['id']);
        }

        $request->setPresenterName(self::DEFAULT_MODULE . ':' . self::DEFAULT_PRESENTER);
        $request->setParameters(array(
                'module' => self::DEFAULT_MODULE,
                'domain' => $domain,
                'presenter' => self::DEFAULT_PRESENTER,
                'action' => self::DEFAULT_ACTION,
                'locale' => $locale,
                'slug' => $slug,
            ) + $parameters);

        return $request;
    }


    public function cleanCache()
    {
        if ($this->useCache) {
            $this->cache->clean([
                Nette\Caching\Cache::TAGS => array(RouteEntity::CACHE),
            ]);
        }
    }




    function getSubscribedEvents()
    {
        return [
            PackageFacade::EVENT_COPY_PACKAGE => 'cleanCache',
            PackageFacade::EVENT_CHANGE_PACKAGE => 'cleanCache',
            PackageFacade::EVENT_REMOVE_PACKAGE => 'cleanCache',
            Devrun\CmsModule\Facades\DomainFacade::class . '::onChangeDomain' => 'cleanCache',
        ];

    }
}