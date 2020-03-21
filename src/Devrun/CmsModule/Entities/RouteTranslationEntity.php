<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    RouteTranslationEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\Attributes\Translation;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Zenify\DoctrineBehaviors\Entities\Attributes\Translatable as ZenifyTranslatable;

/**
 * Class RouteTranslationEntity
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\RouteTranslationRepository")
 * @ORM\Table(name="route_translation", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="domain_url_locale_idx", columns={"domain_id", "domain_url", "locale"}),
 *     @ORM\UniqueConstraint(name="url_locale_idx", columns={"url", "locale"}),
 * }, indexes={
 *     @ORM\Index(name="url_idx", columns={"url"}),
 *     @ORM\Index(name="domain_url_idx", columns={"domain_url"}),
 * })
 *
 * @package Devrun\CmsModule\Entities
 */
class RouteTranslationEntity
{

    use Translation;
    use MagicAccessors;
//    use ZenifyTranslatable;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name = '';

    /**
     * @var string
     * @ORM\Column(type="string", options={"default" : ""})
     */
    protected $title = '';

//    /**
//     * @var string
//     * @ORM\Column(type="string")
//     */
//    protected $domain = '';

    /**
     * @var DomainEntity|null
     * @ORM\ManyToOne(targetEntity="DomainEntity", inversedBy="routeTranslations")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $domain;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $url;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $domainUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $keywords;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $notation;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


    /**
     * @param string $domainUrl
     */
    public function setDomainUrl(string $domainUrl = null)
    {
        $this->domainUrl = $domainUrl;
    }

    /**
     * @return null|string
     */
    public function getDomainUrl()
    {
        return $this->domainUrl;
    }



    /**
     * @param mixed $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param mixed $notation
     */
    public function setNotation($notation)
    {
        $this->notation = $notation;
    }

    /**
     * @return DomainEntity
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param DomainEntity $domain
     *
     * @return RouteTranslationEntity
     */
    public function setDomain(DomainEntity $domain = null)
    {
        $this->domain = $domain;
        return $this;
    }




    public function __clone()
    {
        $this->id = NULL;
        $this->translatable = null;
    }



}