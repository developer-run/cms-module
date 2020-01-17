<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImageCategory.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class ImageCategory
 *
 * @ORM\Entity
 * @ORM\Table(name="imageCategory", indexes={@ORM\Index(name="image_category_name_idx", columns={"title"})})
 *
 * @package Devrun\CmsModule\Entities
 */
class ImageCategoryEntity
{

    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use BlameableTrait;


    /**
     * @var ImageCategory[]
     *
     * @ORM\OneToMany(targetEntity="ImagesEntity", mappedBy="category")
     */
    private $images;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;






}