<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    ArticleControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Flame\Application\UI\Control;

class ArticleControl extends Control
{


    public function render()
    {
        $template = $this->getTemplate();


        $template->render();
    }


}