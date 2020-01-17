<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    RouteEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\Attributes\Translatable;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\Entities\MagicAccessors;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\Translator;
use Nette\Utils\DateTime;
use Zenify\DoctrineBehaviors\Entities\Attributes\Translatable as ZenifyTranslatable;

/**
 * Class RouteEntity
 *
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\RouteRepository")
 * _@_ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="route", indexes={
 *     @ORM\Index(name="expired_idx", columns={"expired"}),
 *     @ORM\Index(name="released_idx", columns={"released"}),
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uri_params_idx", columns={"uri", "params"})
 * })
 *
 * @package Devrun\CmsModule\Entities
 * @method getUri();
 * @method getPublished();
 * @method RouteTranslationEntity translate($lang = '', $fallbackToDefault = true)
 */
class RouteEntity
{
    const CACHE = 'Cms.RouteEntity';

    /** @var array */
    protected static $robotsValues = array(
        'index, follow',
        'noindex, follow',
        'index, nofollow',
        'noindex, nofollow',
    );

    /** @var array */
    protected static $changefreqValues = array(
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    );

    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use Translatable;
//    use ZenifyTranslatable;


    /** @var Translator */
    private $translator;

    /**
     * @var PageEntity
     * @ORM\ManyToOne(targetEntity="PageEntity", inversedBy="routes")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $page;

    /**
     * @var RouteEntity
     * @ORM\ManyToOne(targetEntity="RouteEntity", inversedBy="children")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var RouteEntity[]
     * @ORM\OneToMany(targetEntity="RouteEntity", mappedBy="parent", fetch="EXTRA_LAZY")
     */
    protected $children;

    /**
     * @var PackageEntity
     * @ORM\ManyToOne(targetEntity="PackageEntity", inversedBy="routes", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $package;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $uri;


    /**
     * @var array
     * @ORM\Column(type="string", nullable=true)
     */
    protected $params;


    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $published = FALSE;

    /** @ORM\Column(type="string") */
    protected $robots = '';

    /** @ORM\Column(type="string", nullable=true) */
    protected $changefreq;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $priority;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $expired;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $released;

    /**
     * RouteEntity constructor.
     */
    public function __construct(PageEntity $page, ITranslator $translator)
    {
        $this->page = $page;
        $page->setMainRoute($this);
        $this->released = new DateTime();
        $this->children = new ArrayCollection();
        $this->translator = $translator;

        $this->setDefaultLocale($this->translator->getDefaultLocale());
        $this->setCurrentLocale($this->translator->getLocale());
    }


    /**
     * @param string $uri
     *
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = json_encode($params);
        return $this;
    }


    /**
     * @return array
     */
    public function getParams()
    {
        return json_decode($this->params, true);
    }



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
    public function setPage(PageEntity $page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return PackageEntity
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param PackageEntity $package
     *
     * @return $this
     */
    public function setPackage($package)
    {
        $this->package = $package;
        return $this;
    }



    /**
     * @return RouteEntity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param RouteEntity $parent
     */
    public function setParent(RouteEntity $parent = NULL)
    {
        if ($this->getParent() == $parent) {
            return;
        }

        $this->parent = $parent;
        if ($parent) {
            $parent->children[] = $this;
        }

    }


    /**
     * @param $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }


    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        if ($this->children === NULL) {
            $this->children = new ArrayCollection;
        }
        return $this->children;
    }

    /*
     * ----------------------------------------------------------------------------------------
     * translated properties
     * ----------------------------------------------------------------------------------------
     */

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->translate($this->currentLocale, false)->setName($name);
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
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->translate($this->currentLocale, false)->setUrl($url);
        return $this;
    }


    public function getName()
    {
        return $this->translate()->name;
    }

    public function getTitle()
    {
        return $this->translate()->title;
    }

    public function getUrl()
    {
        return $this->translate()->getUrl();
    }

    public function getDomainUrl()
    {
        return $this->translate()->getDomainUrl();
    }


    public function setDomain(DomainEntity $domain = null)
    {
        $this->translate($this->currentLocale, true)->setDomain($domain);
        return $this;
    }


    public function setDomainUrl($url)
    {
        $this->translate($this->currentLocale, true)->setDomainUrl($url);
        return $this;
    }


    /**
     * @param $keywords
     *
     * @return $this
     */
    public function setKeywords($keywords)
    {
        $this->translate($this->currentLocale, false)->setKeywords($keywords);
        return $this;
    }


    public function getKeywords()
    {
        return $this->translate()->keywords;
    }


    public function setDescription($description)
    {
        $this->translate($this->currentLocale, false)->setDescription($description);
        return $this;
    }


    public function getDescription()
    {
        return $this->translate()->description;
    }


    public function setNotation($notation)
    {
        $this->translate($this->currentLocale, false)->setNotation($notation);
        return $this;
    }


    public function getNotation()
    {
        return $this->translate()->notation;
    }


    public function getDomain()
    {
        return $this->translate()->getDomain();
    }




    /**
     * @param bool $published
     *
     * @return $this
     */
    public function setPublished(bool $published)
    {
        $this->published = $published;
        return $this;
    }




    public function __clone()
    {
        $this->id = NULL;
        $this->translations = [];
    }


}