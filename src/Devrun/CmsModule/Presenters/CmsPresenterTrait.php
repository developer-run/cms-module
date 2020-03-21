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
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\CmsModule\Repositories\PageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;

trait CmsPresenterTrait
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



    public function getPackage()
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
    public function getPackageEntity(): PackageEntity
    {
        if ($this->_packageEntity === null) {
            if ($package = $this->getPackage()) {
                $this->_packageEntity = $this->packageRepository->find($package);
            }
        }

        return $this->_packageEntity;
    }

    /**
     * @return RouteEntity
     */
    public function getRouteEntity(): RouteEntity
    {
        if ($this->_routeEntity === null) {
            if ($route = $this->route) {
                $this->_routeEntity = $this->routeRepository->find($route);
            }
        }

        return $this->_routeEntity;
    }

    /**
     * @return PageEntity
     */
    public function getPageEntity(): PageEntity
    {
        if ($this->_pageEntity === null) {
            if ($page = $this->page) {
                $this->_pageEntity = $this->packageRepository->find($page);
            }
        }

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