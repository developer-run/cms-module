<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PageRouteFactory.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Routes;

use Devrun\CmsModule\Repositories\RouteRepository;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Caching\IStorage;

class PageRouteFactory
{


    /**
     * @return IRouter
     */
    public static function createRouter(\Devrun\CmsModule\Routes\PageRoute $pageRoute)
    {

        $router = new RouteList();
        $router[] = $pageRoute;

        return $router;
    }


}