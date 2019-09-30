<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    EnvironmentControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Flame\Application\UI\Control;
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

    public function render()
    {
        /** @var Template $template */
        $template = $this->createTemplate();

        $template->editTranslateSignal = $this->getPresenter()->link(':Cms:Translate:update');
        $template->editArticleSignal = $this->getPresenter()->link(':Cms:Article:Translate:update');
        $template->render();
    }


}