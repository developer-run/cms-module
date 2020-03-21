<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    EnvironmentControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;
use Nette\Bridges\ApplicationLatte\Template;

interface IJSEnvironmentControl
{
    /** @return JSEnvironmentControl */
    function create();
}

/**
 * Class JSEnvironmentControl
 *
 * @package Devrun\CmsModule\Controls
 */
class JSEnvironmentControl extends Control
{

    /**
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function render()
    {
        /** @var Template $template */
        $template = $this->createTemplate();

        $template->editTranslateSignal = $this->getPresenter()->link(':Cms:Translate:update');
        $template->render();
    }


}