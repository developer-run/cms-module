<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    PackageEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\Attributes\Translatable;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\Translator;
use Nette\Utils\Strings;

/**
 * Class PackageEntity
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\PackageRepository")
 * @ORM\Table(name="package", indexes={
 *     @ORM\Index(name="package_name_idx", columns={"name"}),
 *     @ORM\Index(name="package_module_idx", columns={"module"}),
 *     @ORM\Index(name="package_published_idx", columns={"published"}),
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name_package_idx", columns={"name", "module"}),
 *     @ORM\UniqueConstraint(name="base_package_idx", columns={"base", "module"}),
 * })
 *
 * @package ContestModule\Entities
 * @method getName()
 * @method getAnalyticCode()
 * @method PackageTranslationEntity translate($lang = '', $fallbackToDefault = true)
 */
class PackageEntity
{
    use MagicAccessors;
    use IdentifiedEntityTrait;
    use DateTimeTrait;
    use Translatable;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;


    /**
     * @var ModuleEntity|null
     * @ORM\ManyToOne(targetEntity="ModuleEntity", inversedBy="packages")
     * @ORM\JoinColumn(name="module", onDelete="CASCADE")
     */
    protected $module;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $published = false;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $base;

//    /**
//     * @var string
//     * @ORM\Column(type="string")
//     */
//    protected $module;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $analyticCode;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $themeVariables = [];

    /**
     * @var string
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    protected $themeVersion = 1;


    /**
     * @var integer
     * @ORM\Column(type="smallint", options={"default": 1})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $position = 1;


    /**
     * @var UserEntity|null
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;


    /**
     * @var UserEntity[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Devrun\CmsModule\Entities\UserEntity", mappedBy="packages")
     */
    protected $users;


    /**
     * @var RouteEntity[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="RouteEntity", mappedBy="package")
     */
    protected $routes;

    /**
     * PackageEntity constructor.
     *
     * @param string                 $name
     * @param string                 $module
     * @param Translator|ITranslator $translator
     */
    public function __construct($name, $module, ITranslator $translator)
    {
        $this->name   = $name;
        $this->module = $module;
        $this->setDefaultLocale($translator->getDefaultLocale());
        $this->setCurrentLocale($translator->getLocale());
        $this->users  = new ArrayCollection();
        $this->routes = new ArrayCollection();
    }

    /**
     * @param UserEntity $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
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
     * @param bool $published
     * @return PackageEntity
     */
    public function setPublished(bool $published): PackageEntity
    {
        $this->published = $published;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }


    /**
     * @return RouteEntity[]|ArrayCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }


    /**
     * @param string $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return ModuleEntity|null
     */
    public function getModule(): string
    {
        return $this->module;
    }


    /**
     * @param array $theme
     *
     * @return $this
     */
    public function setThemeVariables($theme)
    {
        $this->themeVariables = $theme;
        $this->themeVersion++;
        return $this;
    }


    /**
     * @return array
     */
    public function getThemeVariables()
    {
        return $this->themeVariables;
    }

    /**
     * @return string
     */
    public function getThemeVersion()
    {
        return $this->themeVersion;
    }

    /**
     * @param string $analyticCode
     *
     * @return PackageEntity
     */
    public function setAnalyticCode(string $analyticCode): PackageEntity
    {
        $this->analyticCode = $analyticCode;
        return $this;
    }


    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->translate($this->currentLocale, false)->setTitle($title);
        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->translate($this->currentLocale, false)->setDescription($description);
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->translate()->getTitle();
    }

    public function getDescription()
    {
        return $this->translate()->getDescription();
    }

    public function getDomain()
    {
        return $this->translate()->getDomain();
    }


    public function setDomain(DomainEntity $domainEntity = null)
    {
        $this->translate($this->currentLocale, true)->setDomain($domainEntity);
        return $this;
    }





    function __toString()
    {
        return Strings::webalize($this->name);
    }


}