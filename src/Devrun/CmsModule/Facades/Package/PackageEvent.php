<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PackageEvent.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\Package;

use Nette\SmartObject;

class PackageEvent
{
    const COPY_EVENT = "Devrun\CmsModule\Facades\Package::onCopyRoute";

    use SmartObject;


    /** @var array event */
    public $onCopyRoute = [];







}