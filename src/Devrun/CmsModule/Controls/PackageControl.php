<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    PackageControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Forms\IDevrunForm;
use Devrun\CmsModule\Presenters\PagePresenter;
use Devrun\CmsModule\Repositories\RouteRepository;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Tracy\Debugger;

interface IPackageControlFactory
{
    /** @return PackageControl */
    function create();
}

/**
 * Class PackageControl
 *
 * @package Devrun\CmsModule\Controls
 * @method onSuccess($values)
 */
class PackageControl extends Control
{

    /** @var IDevrunForm @inject */
    public $devrunForm;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var User @inject */
    public $user;

    private $routeList = [];

    public $onSuccess = [];

    /** @var int */
    private $selectRoute;



    protected function attached($presenter): void
    {
        parent::attached($presenter);

        if ($presenter instanceof \Nette\Application\IPresenter) {

            if ($this->selectRoute) {
                $this["form-route"]->setDefaultValue($this->selectRoute);
            }
        }
    }


    public function render()
    {
        $template = $this->createTemplate();
        $template->render();
    }


    protected function createComponentForm()
    {
        $form = $this->devrunForm->create();
        $form
            ->setFormClass(['form-inline ajax'])
            ->addSelect('route', null, $this->routeList)
            ->setAttribute('placeholder', "placeholder.selectPackage")
            ->getControlPrototype()->addAttributes(['class' => 'auto-change']);

        $form->bootstrap3Render();
        $form->onSuccess[] = function ($form, $values) {
            $this->onSuccess($values);
        };

        return $form;
    }


    /**
     * @param RouteEntity[] $routes
     *
     * @return $this
     */
    public function setRouteList($routes)
    {
        $packages = [];
        foreach ($routes as $id => $route) {
            if ($package = $route->getPackage()) {
                $packages[$id] = $package->getName();

            } else {
                $packages[$id] = "-";
            }
        }

        $this->routeList = $packages;
        return $this;
    }


    /**
     * @param int $selectPackage
     *
     * @return $this
     */
    public function setSelectRouteByPackage(int $selectPackage = null)
    {
        $this->selectRoute = $selectPackage;
        return $this;
    }



}