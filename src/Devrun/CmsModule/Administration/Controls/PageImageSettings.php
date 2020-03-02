<?php
/**
 * This file is part of devrun-care.
 * Copyright (c) 2018
 *
 * @file    PageImageSettings.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Kdyby\Translation\Translator;

class PageImagesSettings
{
    /** @var PageImageSettings[] */
    private $pageImages = [];

    /** @var Translator */
    private $translator;

    /**
     * PageImagesSettings constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }


    public function add($pageName, PageImageSettings $pageImageSettings)
    {
        $this->pageImages[$pageName] = $pageImageSettings;
        return $this;
    }


    public function setImages(array $pageImageSettings)
    {
        $this->pageImages = $pageImageSettings;

//        dump($this->pageImages);
//        dump($pageImageSettings);
//        die();


    }

    /**
     * @param null $pageName
     *
     * @return PageImageSettings|PageImageSettings[]
     */
    public function getPageImages($pageName = null)
    {
        if ($pageName) {
            $locale = $this->translator->getLocale();
            return isset($this->pageImages[$locale][$pageName]) ? $this->pageImages[$locale][$pageName] : [];
        }

        return $this->pageImages;
    }








}






class PageImageSettings
{
    /** @var string */
    private $path;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    private $fileName;

    /**
     * PageImageSettings constructor.
     *
     * @param $fileName
     */
    public function __construct($fileName = null)
    {
        if (file_exists($fileName)) {
            $this->fileName = $fileName;
        }

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
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return null
     */
    public function getFileName()
    {
        return $this->fileName;
    }





}