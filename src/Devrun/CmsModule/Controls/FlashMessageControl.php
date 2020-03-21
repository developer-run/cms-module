<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2018
 *
 * @file    FlashMessageControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;

interface IFlashMessageControlFactory
{
    /** @return FlashMessageControl */
    function create();
}

class FlashMessageControl extends Control
{

    const TOAST_TYPE = 'toast';

    const TOAST_INFO = [
        'hideAfter' => 10000, 'bgColor' => '#5050A0', 'showHideTransition' => 'slide',
    ];
    const TOAST_SUCCESS = [
        'hideAfter' => 12000, 'bgColor' => '#50A050', 'icon' => 'success', 'showHideTransition' => 'slide',
    ];
    const TOAST_DANGER = [
        'hideAfter' => 20000, 'bgColor' => '#B03030', 'textColor' => '#F0F000', 'icon' => 'warning','heading' => 'Warning', 'showHideTransition' => 'plain',
    ];
    const TOAST_CRITICAL = [
        'hideAfter' => 'false', 'bgColor' => '#B03030', 'textColor' => '#F0F000', 'icon' => 'error', 'heading' => 'Error', 'showHideTransition' => 'fade',
    ];
    const TOAST_WARNING = [
        'hideAfter' => 15000, 'bgColor' => '#B0A030', 'textColor' => '#F0F000', 'icon' => 'warning', 'heading' => 'Warning', 'showHideTransition' => 'plain',
    ];


    public function render()
    {
        $template = $this->getTemplate();
        $template->flashes = $this->getParent()->template->flashes;
        $template->render();
    }


    public function renderToast()
    {
        $template = $this->getTemplate();
        $template->flashes = $this->getParent()->template->flashes;
        $template->setFile(__DIR__ . '/FlashToastMessage.latte')->render();
    }


}