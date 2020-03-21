<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    Images.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\Attributes\Translatable;
use Devrun\DoctrineModule\Entities\ImageTrait;
use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Kdyby\Translation\Translator;
use Zenify\DoctrineBehaviors\Entities\Attributes\Translatable as ZenifyTranslatable;

/**
 * Class Images
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\ImageRepository")
 * @ORM\Table(name="images", indexes={
 *     @ORM\Index(name="image_public_idx", columns={"public"}),
 * })
 *
 * @package Devrun\CmsModule\Entities
 * @method ImagesTranslationEntity translate($lang = '', $fallbackToDefault = true)
 */
class ImagesEntity implements ITranslatableImage, IImage
{
    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use BlameableTrait;
//    use ImageTrait;
    use Translatable;
//    use ZenifyTranslatable;


    /**
     * var ImageCategoryEntity
     * @ORM\ManyToOne(targetEntity="ImageCategoryEntity", inversedBy="images")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $category;

    /**
     * @var ImageIdentifyEntity
     * @ORM\ManyToOne(targetEntity="ImageIdentifyEntity", inversedBy="images", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $identify;

    /**
     * var RouteEntity
     * @ORM\ManyToOne(targetEntity="RouteEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $route;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $public = true;


    /**
     * ImagesEntity constructor.
     *
     * @param Translator          $translator
     * @param ImageIdentifyEntity $imageIdentifyEntity
     */
    public function __construct(Translator $translator, ImageIdentifyEntity $imageIdentifyEntity)
    {
        $this->setDefaultLocale($translator->getDefaultLocale());
        $this->setCurrentLocale($translator->getLocale());
        $this->identify = $imageIdentifyEntity;
    }



    /*
     * ----------------------------------------------------------------------------------------
     * getters / setters properties
     * ----------------------------------------------------------------------------------------
     */

    /**
     * @return RouteEntity
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param RouteEntity $route
     *
     * @return $this
     */
    public function setRoute(RouteEntity $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return string
     */
    public function getReferenceIdentifier(): string
    {
        return $this->getIdentify()->getReferenceIdentifier() ? $this->getIdentify()->getReferenceIdentifier() : '';
    }

    /**
     * @param string $referenceIdentifier
     *
     * @return $this
     */
    public function setReferenceIdentifier(string $referenceIdentifier)
    {
        $this->getIdentify()->setReferenceIdentifier($referenceIdentifier);
        return $this;
    }

    /**
     * @return ImageIdentifyEntity
     */
    public function getIdentify(): ImageIdentifyEntity
    {
        return $this->identify;
    }

    /**
     * @param ImageIdentifyEntity $identify
     *
     * @return ImagesEntity
     */
    public function setIdentify(ImageIdentifyEntity $identify): ImagesEntity
    {
        $this->identify = $identify;
        return $this;
    }




    /**
     * @param $namespace
     *
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->getIdentify()->setNamespace($namespace);
        return $this;
    }

    public function getNamespace()
    {
        return $this->getIdentify()->getNamespace();
    }







    /*
     * ----------------------------------------------------------------------------------------
     * translated properties
     * ----------------------------------------------------------------------------------------
     */
    public function setPath(string $path)
    {
        $this->translate()->setPath($path);
        return $this;
    }
    public function getPath()
    {
        return $this->translate()->getPath();
    }


    public function setName(string $name)
    {
        $this->translate()->setName($name);
        return $this;
    }
    public function getName()
    {
        return $this->translate()->getName();
    }


    public function setDescription($description)
    {
        $this->translate()->description = $description;
        return $this;
    }
    public function getDescription()
    {
        return $this->translate()->description;
    }


    public function setAlt(string $alt)
    {
        $this->translate()->setAlt($alt);
        return $this;
    }
    public function getAlt()
    {
        return $this->translate()->alt;
    }


    public function setWidth(int $width)
    {
        $this->translate()->width = $width;
        return $this;
    }
    public function getWidth()
    {
        return $this->translate()->width;
    }


    public function setHeight(int $height)
    {
        $this->translate()->height = $height;
        return $this;
    }
    public function getHeight()
    {
        return $this->translate()->height;
    }


    public function setType(string $type)
    {
        $this->translate()->type = $type;
        return $this;
    }
    public function getType()
    {
        return $this->translate()->type;
    }

    public function setIdentifier(string $identifier)
    {
        $this->translate()->setIdentifier($identifier);
        return $this;
    }
    public function getIdentifier()
    {
        return $this->translate()->identifier;
    }


    public function setSha(string $sha)
    {
        $this->translate()->setSha($sha);
        return $this;
    }
    public function getSha()
    {
        return $this->translate()->sha;
    }



    /*
     * ----------------------------------------------------------------------------------------
     * internal properties
     * ----------------------------------------------------------------------------------------
     */
    public function __clone()
    {
        $this->id = NULL;
        $this->createdBy = NULL;
        $this->updatedBy = NULL;
        $this->deletedBy = NULL;
        $this->translations = [];
    }


}