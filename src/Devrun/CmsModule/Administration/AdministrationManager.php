<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2016
 *
 * @file    AdministrationManager.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration;

use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * Class AdministrationManager
 *
 * @package Devrun\CmsModule\Administration
 * @property-read string $defaultPresenter
 * @property-read array  $login
 * @property-read string $theme
 */
class AdministrationManager
{

    use SmartObject;

    /** @var User */
    private $user;

    /** @var array */
    private $administrationPages = array();

    /** @var array components, only clean component with out factory settings (in presenter), can by located anywhere */
    private $administrationComponents = array();

    /** @var array components, with factory settings (in presenter), can by located only in actual presenter */
    private $administrationFactories = array();

    private $userPermissions = array();


    /** @var string */
    private $defaultPresenter;

    /** @var array */
    private $login;

    /** @var string */
    private $theme;

    /**
     * AdministrationFacade constructor.
     *
     * @param string $defaultPresenter
     */
    public function __construct($defaultPresenter, $login, $theme, User $user)
    {
        $this->defaultPresenter = $defaultPresenter;
        $this->login            = $login;
        $this->theme            = $theme;
        $this->user             = $user;
    }


    public function addUserPermission($service, array $tags)
    {
        $this->userPermissions[$service] = $tags;
    }


    /**
     * Add Administration page to navigation
     *
     * @inheritdoc startup from di
     * @param       $tags
     * @throws \Nette\Utils\AssertionException
     */
    public function addAdministrationPage($service, array $tags)
    {
        Validators::assertField($tags, 'link');
        Validators::assertField($tags, 'name');
        Validators::assertField($tags, 'icon');
        Validators::assertField($tags, 'category');
        Validators::assertField($tags, 'description');

        $this->administrationPages[$service] = array(
            'type'        => 'page',
            'service'     => $service,
            'name'        => $tags['name'],
            'link'        => $tags['link'],
            'icon'        => $tags['icon'],
            'category'    => $tags['category'],
            'description' => $tags['description'],
            'priority'    => isset($tags['priority']) ? $tags['priority'] : 0,
        ) + $tags;
    }


    /**
     * Add Administration component to navigation
     *
     * @inheritdoc startup from di
     * @param       $service
     * @param array $tags
     * @throws \Nette\Utils\AssertionException
     */
    public function addAdministrationComponent($service, array $tags)
    {
        Validators::assertField($tags, 'name');
        Validators::assertField($tags, 'category');

        $this->administrationComponents[$service] = array(
            'type'     => 'control',
            'service'  => $service,
            'name'     => $tags['name'],
            'category' => $tags['category'],
            'priority' => isset($tags['priority']) ? $tags['priority'] : 0,
        ) + $tags;
    }


    /**
     * Add Administration component/factory to navigation
     *
     * @inheritdoc startup from di
     * @param       $service
     * @param array $tags
     * @throws \Nette\Utils\AssertionException
     */
    public function addAdministrationFactory($service, array $tags)
    {
        Validators::assertField($tags, 'name');
        Validators::assertField($tags, 'category');

        $this->administrationFactories[$service] = array(
            'type'     => 'factory',
            'service'  => $service,
            'name'     => lcfirst($tags['name']),
            'category' => $tags['category'],
            'priority' => isset($tags['priority']) ? $tags['priority'] : 0,
        ) + $tags;
    }

    /**
     * Get user permission services
     *
     * @return array
     */
    public function getUserPermissions(): array
    {
        return $this->userPermissions;
    }


    /**
     * Get Administration pages as array
     *
     * @return array
     */
    public function getAdministrationPages()
    {
        return $this->administrationPages;
    }

    /**
     * Get Administration pages by category
     *
     * @param      $category
     * @param bool $sort
     *
     * @return array
     */
    public function getAdministrationPagesByCategory($category, $byPrefix = false, $sort = true)
    {
        $result = [];

        foreach ($this->getAdministrationPages() as $administrationPage) {
            if ($this->isAllowedLink($link = $administrationPage['link'])) {
                $equal = $byPrefix
                    ? Strings::startsWith($administrationPage['category'], $category)
                    : $category == $administrationPage['category'];

                if ($equal)
                    $result[] = $administrationPage;
            }
        }

        if ($sort && $result) {
            $this->sortByPriority($result);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAdministrationComponents()
    {
        return $this->administrationComponents;
    }

    /**
     * Get Administration components by category
     *
     * @param      $category
     * @param bool $sort
     *
     * @return array
     */
    public function getAdministrationComponentsByCategory($category, $sort = true)
    {
        $result = [];

        foreach ($this->getAdministrationComponents() as $administrationComponent) {
            if ($category == $administrationComponent['category'])
                $result[] = $administrationComponent;
        }

        if ($sort) {
            $this->sortByPriority($result);
        }

        return $result;
    }

    public function getAdministrationItemsByCategory($category, $sort = true)
    {
        $result = [];
        $items  = $this->getAdministrationComponents() + $this->administrationFactories + $this->getAdministrationPages();

        foreach ($items as $item) {
            if ($category == $item['category'])
                $result[] = $item;
        }

        if ($sort) {
            $this->sortByPriority($result);
        }

        return $result;
    }


    /**
     * @param array $items
     */
    private function sortByPriority(array & $items)
    {
        usort($items, function ($a, $b) {
            $priorityA = isset($a['priority']) ? intval($a['priority']) : 0;
            $priorityB = isset($b['priority']) ? intval($b['priority']) : 0;

            if ($priorityA == $priorityB) {
                return 0;
            }
            return ($priorityA < $priorityB) ? -1 : 1;
        });

        $sortItems = [];
        foreach ($items as $item) {
            $sn             = str_replace('.', '_', $item['service']);
            $sortItems[$sn] = $item;
        }

        $items = $sortItems;
    }

    /**
     * Return allowed link, example link [:Cms:Submodule:Presenter:  :Cms:Submodule:Presenter:action]
     *
     * @param $link
     *
     * @return bool
     */
    private function isAllowedLink($link)
    {
        $ex   = explode(':', trim($link, ":"));
        $last = end($ex);

        $privilege = \Nette\Application\UI\Presenter::DEFAULT_ACTION;
        if (\Devrun\Utils\Strings::starts_with_lower($last)) {
            $privilege = $last;
            unset($ex[count($ex) - 1]);
        }

        $resource = implode(":", $ex);
        return $this->user->isAllowed($resource, $privilege);
    }



    /**
     * @return array
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }


    /**
     * Get Administration pages as array
     *
     * @return array
     */
    public function getAdministrationNavigation()
    {
        $ret = array();

        foreach ($this->administrationPages as $link => $item) {
            $ret[$item['category']][$link] = $item;
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getDefaultPresenter()
    {
        return $this->defaultPresenter;
    }


}