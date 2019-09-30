<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ModalActionControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Tracy\Debugger;

interface IModalActionControlFactory
{
    /** @return ModalActionControl */
    function create();
}

class ModalActionControl extends Control
{

    /** @var bool */
    private $enable = true;


    protected function attached($presenter)
    {
        if ($presenter instanceof AdminPresenter) {

        }

        parent::attached($presenter);
    }


    public function render()
    {
        $template = $this->getTemplate();

        $template->enable = $this->enable;
        $template->render();

    }

    /**
     * @param bool $enable
     */
    public function setEnable(bool $enable)
    {
        $this->enable = $enable;
    }

}