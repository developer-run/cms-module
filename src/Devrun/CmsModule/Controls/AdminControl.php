<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    AdminControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Presenters\PagePresenter;

/**
 * Class AdminControl
 *
 * @package Devrun\CmsModule\Controls
 * @method onRedraw($control);
 */
class AdminControl extends Control
{

    /** @var array @deprecated use pageRedraw instead */
    public $onRedraw = [];


    protected function pageRedraw()
    {
        $presenter = $this->getPresenter();

        if ($presenter instanceof PagePresenter) {
            $presenter->onPageRedraw($this);
        }

    }



}