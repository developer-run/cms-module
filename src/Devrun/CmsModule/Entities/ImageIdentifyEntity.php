<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ImageIdentifyEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\CmsModule\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;

/**
 * Class ImageIdentifyEntity
 *
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\ImageIdentifyRepository")
 * @ORM\Table(name="image_identify", indexes={
 *    @ORM\Index(name="image_identify_namespace_name_idx", columns={"namespace", "name"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(
 *    name="reference_identifier_unique_idx", columns={"reference_identifier"}
 * )})

 * @package Devrun\CmsModule\Entities
 */
class ImageIdentifyEntity
{

    use Identifier;
    use MagicAccessors;


    /**
     * @var ImagesEntity
     * @ORM\OneToMany(targetEntity="ImagesEntity", mappedBy="identify")
     */
    protected $images;


    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $referenceIdentifier;

    /**
     * @var string namespace
     * @ORM\Column(name="`namespace`", type="string")
     */
    protected $namespace;

    /**
     * @var string system name
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * ImageIdentifyEntity constructor.
     */
    public function __construct($identifier)
    {
        $this->images  = new ArrayCollection();
        $this->setReferenceIdentifier($identifier);
    }

    /**
     * @return string
     */
    public function getReferenceIdentifier(): string
    {
        return $this->referenceIdentifier;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     *
     * @return ImageIdentifyEntity
     */
    public function setNamespace(string $namespace): ImageIdentifyEntity
    {
        $this->namespace = $namespace;
        return $this;
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
     * @return ImageIdentifyEntity
     */
    public function setName(string $name): ImageIdentifyEntity
    {
        $this->name = $name;
        return $this;
    }


    /**
     * @param string $referenceIdentifier
     *
     * @return ImageIdentifyEntity
     */
    public function setReferenceIdentifier(string $referenceIdentifier): ImageIdentifyEntity
    {
        if (!strpos($referenceIdentifier, '/')) {
            throw new InvalidArgumentException('Identifier must have two words [namespace.name]');
        }
        $this->referenceIdentifier = $referenceIdentifier;

        $pathInfo        = pathinfo($referenceIdentifier);
        $identify        = explode('/', $referenceIdentifier);
        $name            = array_pop($identify);
        $name            = $pathInfo['filename'];
        $namespace       = implode('/', $identify);
        $this->name      = $name;
        $this->namespace = $namespace;

        return $this;
    }



}