<?php

namespace Devrun\CmsModule\Forms;

use Nette\SmartObject;

/**
 * Class DevrunFormFactory
 *
 * @package Devrun\CmsModule\Forms
 */
class DevrunFormFactory
{

    use SmartObject;

    /**
     * @return DevrunForm
     */
    public function create()
    {
        return new DevrunForm();
    }


}