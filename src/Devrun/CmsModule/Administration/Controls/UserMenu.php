<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    UserMenu.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;

interface IUserMenuControlFactory
{
    /** @return UserMenu */
    function create();
}

class UserMenu extends Control
{

    public function render()
    {
        $template = $this->getTemplate();
        $template->render();
    }


}