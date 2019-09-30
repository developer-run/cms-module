<?php

namespace Devrun\CmsModule\Forms;

/**
 * Class DevrunFormFactory
 *
 * @package Devrun\CmsModule\Forms
 */
class DevrunFormFactory extends \Nette\Object
{

    /**
     * @return DevrunForm
     */
    public function create()
    {
        return new DevrunForm();
    }


}