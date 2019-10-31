<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    CmsPresenterTrait.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\INavigationPageControlFactory;

trait CmsPresenterTrait
{

    /** @var INavigationPageControlFactory @inject */
    public $navigationPageControlFactory;





    /**
     * @return \Devrun\CmsModule\Controls\NavigationPageControl
     */
    protected function createComponentNavigationPageControl()
    {
        $control = $this->navigationPageControlFactory->create();
        return $control;
    }


}