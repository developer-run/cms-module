<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PackageTranslationEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\Attributes\Translation;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class PackageTranslationEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="package_translation")
 * @package Devrun\CmsModule\Entities
 * @method getTitle()
 * @method getDescription()
 */
class PackageTranslationEntity
{

    use Translation;
    use MagicAccessors;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var DomainEntity|NULL
     * @ORM\ManyToOne(targetEntity="DomainEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $domain;

    /**
     * @param string $title
     *
     * @return PackageTranslationEntity
     */
    public function setTitle(string $title): PackageTranslationEntity
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param mixed $description
     *
     * @return PackageTranslationEntity
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param DomainEntity $domain
     *
     * @return PackageTranslationEntity
     */
    public function setDomain(DomainEntity $domain = null): PackageTranslationEntity
    {
        $this->domain = $domain;
        return $this;
    }


    /**
     * @return DomainEntity|NULL
     */
    public function getDomain()
    {
        return $this->domain;
    }






}