<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    PageSectionsEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class PageSectionsEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="page_sections", indexes={
 *     @ORM\Index(name="page_section_name_idx", columns={"name"}),
 * })
 * @package Devrun\CmsModule\Entities
 */
class PageSectionsEntity
{
    use IdentifiedEntityTrait;
    use MagicAccessors;

    /**
     * @var PageEntity
     * @ORM\ManyToOne(targetEntity="PageEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $page;


    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $name;

    /**
     * @return PageEntity
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param PageEntity $page
     *
     * @return $this
     */
    public function setPage($page = null)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }





    function __toString()
    {
        return (string)$this->page . ":$this->name";
    }


}