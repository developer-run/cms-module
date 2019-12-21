<?php
/**
 * This file is part of devrun-advent_calendar.
 * Copyright (c) 2018
 *
 * @file    RouteRepositorory.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use Devrun\CmsModule\Entities\RouteEntity;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use Tracy\Debugger;

class RouteRepository extends EntityRepository
{

    /** @var \Nette\Application\UI\Presenter */
    private $presenter;

    private $defaultDomain;

    /**
     * RouteRepository constructor.
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
//        $this->presenter = $application->getPresenter();
    }

    /**
     * DI setter
     *
     * @param null $defaultDomain
     */
    public function setDefaultDomain($defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;
    }


    public function isPageClassPublished($pageClass, $package) {
        $result = $this->createQueryBuilder('e')
            ->select('e.published')
            ->join('e.page', 'p')
            ->where('p.class = :class')->setParameter('class', $pageClass)
            ->andWhere('e.package = :package')->setParameter('package', $package)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $result['published'];
    }


    /**
     * @param Presenter|null $presenter
     *
     * @return RouteEntity|null
     */
    public function findRouteFromApplicationPresenter(Presenter $presenter = null)
    {
        if (null === $presenter)
            $presenter = $this->presenter;

        $request       = $presenter->request;
        $action        = $presenter->getAction();
        $requestParams = $request->getParameters();
        $class         = get_class($presenter);

        $actionMethodName = 'action' . ucfirst($action);
        $renderMethodName = 'render' . ucfirst($action);

        $params = [];
        if (method_exists($class, $actionMethodName)) {
            $actionMethod = Nette\PhpGenerator\Method::from([$class, $actionMethodName]);
            $actionParams = $actionMethod->getParameters();
            $params = array_merge($params, $actionParams);
        }
        if (method_exists($class, $renderMethodName)) {
            $renderMethod = Nette\PhpGenerator\Method::from([$class, $renderMethodName]);
            $renderParams = $renderMethod->getParameters();
            $params = array_merge($params, $renderParams);
        }

        $paramsKeys = array_keys($params);
        sort($paramsKeys);

        $findParamsKeys = [];
        foreach ($paramsKeys as $key) {
            if (isset($requestParams[$key])) {
                $findParamsKeys[$key] = is_numeric($requestParams[$key]) ? intval($requestParams[$key]) : $requestParams[$key];
            }
        }

        return $this->findOneBy(['params' => $encodeKey = json_encode($findParamsKeys), 'page.class' => $class], ['translations.name']);
    }


    public function getRouteFromApplicationRequest(Nette\Application\Request $request)
    {
        return ($routeId = $request->getParameter('route'))
            ? $this->find($routeId)
            : null;
    }


    public function getValidDomain(RouteEntity $routeEntity)
    {
        if ($domain = $routeEntity->getDomain()) {
            return ($domain->isValid()) ? $domain->getName() : $this->defaultDomain;
        }

        return $this->defaultDomain;
    }

    public function isSetValidDomain(RouteEntity $routeEntity)
    {
        if ($domain = $routeEntity->getDomain()) {
            return ($domain->isValid() && $domain->getName() != $this->defaultDomain);
        }

        return false;
    }


}