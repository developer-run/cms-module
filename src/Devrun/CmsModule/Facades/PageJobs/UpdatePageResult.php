<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    UpdatePageResult.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\PageJobs;


/**
 * Class UpdatePageResult of event onUpdatePage for listeners result
 *
 * @package Devrun\CmsModule\Listeners
 */
class UpdatePageResult
{
    /** @var string */
    private $parentPageName;

    /**
     * @return string
     */
    public function getParentPageName()
    {
        return $this->parentPageName;
    }

    /**
     * @param string $parentPageName
     */
    public function setParentPageName($parentPageName)
    {
        $this->parentPageName = $parentPageName;
    }




}