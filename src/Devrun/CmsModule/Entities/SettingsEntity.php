<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    Settings.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class Settings
 *
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\SettingsRepository")
 * _@_ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="settings")
 *
 * @package Devrun\CmsModule\Entities
 */
class SettingsEntity
{

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $value;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $validate;



    /**
     * SettingsEntity constructor.
     *
     * @param string $id
     * @param string $value
     */
    public function __construct($id, $value)
    {
        $this->id    = $id;
        $this->value = $value;
    }


    /**
     * @return string
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param string $validate
     */
    public function setValidate($validate)
    {
        $this->validate = $validate;
    }




}