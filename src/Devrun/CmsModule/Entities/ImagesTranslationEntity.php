<?php
/**
 * This file is part of devrun-advent_calendar.
 * Copyright (c) 2018
 *
 * @file    ImagesTranslationEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\ORM\Mapping as ORM;
use Devrun\Doctrine\Entities\Attributes\Translation;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class ImagesTranslationEntity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Entity
 * @ORM\Table(name="images_translation", indexes={
 *     @ORM\Index(name="path_idx", columns={"path"}),
 *     @ORM\Index(name="image_translation_identifier_idx", columns={"identifier"}),
 * })
 *
 * @package Devrun\CmsModule\Entities
 * @method getPath()
 * @method getName()
 *
 */
class ImagesTranslationEntity
{

    use Translation;
    use MagicAccessors;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $alt;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $path;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $identifier;

    /**
     * @var string
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $sha;

    /**
     * @var integer
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $width;

    /**
     * @var integer
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $height;

    /**
     * @var string
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $type;

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


    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param string $identifier
     *
     * @return ImagesTranslationEntity
     */
    public function setIdentifier(string $identifier): ImagesTranslationEntity
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param string $sha
     *
     * @return ImagesTranslationEntity
     */
    public function setSha(string $sha): ImagesTranslationEntity
    {
        $this->sha = $sha;
        return $this;
    }

    /**
     * @param string $alt
     *
     * @return ImagesTranslationEntity
     */
    public function setAlt(string $alt): ImagesTranslationEntity
    {
        $this->alt = $alt;
        return $this;
    }







    public function __clone()
    {
        $this->id = NULL;
        $this->translatable = null;
    }

}