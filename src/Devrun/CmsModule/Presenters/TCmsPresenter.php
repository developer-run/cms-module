<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    CmsPresenterTrait.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\INavigationPageControlFactory;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\NotFoundResourceException;
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\CmsModule\Repositories\PageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\Utils\Debugger;

trait TCmsPresenter
{

    /** @var INavigationPageControlFactory @inject */
    public $navigationPageControlFactory;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var PackageRepository @inject */
    public $packageRepository;

    /** @var PageRepository @inject */
    public $pageRepository;

    /** @var PackageEntity */
    private $_packageEntity;

    /** @var PageEntity */
    private $_pageEntity;

    /** @var RouteEntity */
    private $_routeEntity;

    /** @var int @persistent */
    public $package;

    /** @var int @persistent */
    public $route;

    /** @var int @persistent */
    public $page;



    /**
     * @return int|null
     */
    public function getPackage(): ?int
    {
        return $this->package;
    }

    /**
     * @return int
     */
    public function getRoute(): int
    {
        return $this->route;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }





    /**
     * @return PackageEntity
     */
    public function getPackageEntity($need = true): PackageEntity
    {
        if ($this->_packageEntity === null) {
            if ($package = $this->package) {
                $this->_packageEntity = $this->packageRepository->find($package);
            }
        }

        if ($need && !$this->_packageEntity)
            throw new NotFoundResourceException(__FUNCTION__ . " not found");

        return $this->_packageEntity;
    }

    /**
     * @return RouteEntity
     */
    public function getRouteEntity($need = true): RouteEntity
    {
        if ($this->_routeEntity === null) {
            if ($route = $this->route) {
                $this->_routeEntity = $this->routeRepository->find($route);
            }
        }

        if ($need && !$this->_routeEntity)
            throw new NotFoundResourceException(__FUNCTION__ . " not found");

        return $this->_routeEntity;
    }

    /**
     * @return PageEntity
     */
    public function getPageEntity($need = true): PageEntity
    {
        if ($this->_pageEntity === null) {
            if ($page = $this->page) {
                $this->_pageEntity = $this->pageRepository->find($page);
            }
        }

        if ($need && !$this->_pageEntity)
            throw new NotFoundResourceException(__FUNCTION__ . " not found");

        return $this->_pageEntity;
    }




    /**
     * @return \Devrun\CmsModule\Controls\NavigationPageControl
     */
    protected function createComponentNavigationPageControl()
    {
        $control = $this->navigationPageControlFactory->create();
        return $control;
    }


}