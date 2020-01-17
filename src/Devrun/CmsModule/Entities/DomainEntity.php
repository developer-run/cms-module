<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    DomainEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Devrun\CmsModule\Entities\UserEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class DomainEntity
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\DomainRepository")
 * @ORM\Table(name="domain", indexes={
 *     @ORM\Index(name="domain_name_idx", columns={"name"}),
 *     @ORM\Index(name="domain_valid_idx", columns={"valid"}),
 * })
 *
 * @package Devrun\CmsModule\Entities
 */
class DomainEntity
{

    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;


    /**
     * @var UserEntity|null
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", name="`name`")
     */
    protected $name;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $valid;

    /**
     * @var RouteTranslationEntity[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="RouteTranslationEntity", mappedBy="domain")
     */
    protected $routeTranslations;


    /**
     * DomainEntity constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->routeTranslations = new ArrayCollection();
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DomainEntity
     */
    public function setName(string $name): DomainEntity
    {
        $this->name = $name;
        return $this;
    }


    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     *
     * @return DomainEntity
     */
    public function setValid(bool $valid): DomainEntity
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @return UserEntity|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserEntity|null $user
     *
     * @return DomainEntity
     */
    public function setUser(UserEntity $user = null)
    {
        $this->user = $user;
        return $this;
    }







    function __toString()
    {
        return $this->name;
    }


}