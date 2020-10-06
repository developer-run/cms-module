<?php

namespace Devrun\CmsModule\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ModuleEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="module")
 *
 * @package Devrun\CmsModule\Entities
 */
class ModuleEntity
{

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var PageEntity[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="PageEntity", mappedBy="module")
     */
    protected $pages;

    /**
     * @var PackageEntity[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="PackageEntity", mappedBy="module")
     */
    protected $packages;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=32)
     * @var string|null
     */
    private $id;

    /**
     * ModuleEntity constructor.
     */
    public function __construct()
    {
        $this->pages    = new ArrayCollection();
        $this->packages = new ArrayCollection();
    }


    /**
     * @return integer
     */
    final public function getId()
    {
        return $this->id;
    }


    public function __clone()
    {
        $this->id = NULL;
    }

    public function __toString()
    {
        return $this->id;
    }

}