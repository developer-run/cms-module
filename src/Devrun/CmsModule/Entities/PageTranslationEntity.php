<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    PageTranslationEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\Attributes\Translation;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;

/**
 * Class PageTranslationEntity
 * @ORM\Entity
 * @ORM\Table(name="page_translation", indexes={
 *     @ORM\Index(name="title_idx", columns={"title"}),
 * })
 *
 * @package Devrun\CmsModule\Entities
 * @method getTitle()
 * @method getCategoryName()
 */
class PageTranslationEntity
{

    use Translation;
    use MagicAccessors;


    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title = '';

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $categoryName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="text", length=65536, nullable=true)
     */
    protected $notation;

    /**
     * @param string $title
     *
     * @return PageTranslationEntity
     */
    public function setTitle(string $title): PageTranslationEntity
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param mixed $categoryName
     *
     * @return PageTranslationEntity
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function clearCategoryName()
    {
        $this->categoryName = null;
        return $this;
    }

    /**
     * @param mixed $description
     *
     * @return PageTranslationEntity
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param mixed $notation
     *
     * @return PageTranslationEntity
     */
    public function setNotation($notation)
    {
        $this->notation = $notation;
        return $this;
    }






}