<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    CreatePageResult.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\PageJobs;

use Devrun\CmsModule\Entities\PageEntity;

/**
 * Class CreatePageResult of event onCreatePage for listeners result
 *
 * @package Devrun\CmsModule\Listeners
 */
class CreatePageResult
{
    /** @var PageEntity */
    private $parentPage;

    /**
     * @return PageEntity
     */
    public function getParentPage()
    {
        return $this->parentPage;
    }

    /**
     * @param PageEntity $parentPage
     */
    public function setParentPage($parentPage)
    {
        $this->parentPage = $parentPage;
    }




}