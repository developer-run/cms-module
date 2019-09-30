<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    TabsContent.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Flame\Application\UI\Control;

interface ITabsContentControlFactory
{
    /** @return TabsContentControl */
    function create();
}

class TabsContentControl extends Control
{

    public function render()
    {
        $template = $this->getTemplate();





        $template->render();
    }

}