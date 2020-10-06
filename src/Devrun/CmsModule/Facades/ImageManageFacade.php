<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImageFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Facades\ImageJobs\ImageJob;
use Devrun\CmsModule\Facades\ImageJobs\PageImageJob;
use Devrun\CmsModule\Forms\IImageFormFactory;
use Devrun\CmsModule\Forms\IImagesFormFactory;
use Devrun\CmsModule\InvalidStateException;
use Devrun\CmsModule\Repositories\ImageIdentifyRepository;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Storage\ImageManageStorage;
use Kdyby\Events\Subscriber;
use Kdyby\Monolog\Logger;
use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\SmartObject;
use Nette\Utils\Image;
use Tracy\Debugger;

/**
 * Class ImageManageFacade
 *
 * @package Devrun\CmsModule\Facades
 */
class ImageManageFacade implements Subscriber
{
    use SmartObject;

    /** @var ImageManageStorage */
    private $imageManageStorage;

    /** @var IImagesFormFactory @inject */
    public $imagesFormFactory;

    /** @var IImageFormFactory @inject */
    public $imageFormFactory;

    /** @var ImageRepository @inject */
    public $imageRepository;

    /** @var ImageIdentifyRepository @inject */
    public $imageIdentifyRepository;

    /** @var Translator @inject */
    public $translator;

    /** @var array of config image */
    private $configEmptyImage;

    /** @var Logger */
    private $logger;


    /** @var ImageJob */
    private $imageJob;

    /** @var PageImageJob */
    private $pageImageJob;



    /**
     * ImageEntityFacade constructor.
     *
     * @param ImageManageStorage $imageManageStorage
     */
    public function __construct(ImageManageStorage $imageManageStorage, Logger $logger)
    {
        $this->logger = $logger;
        $this->imageManageStorage = $imageManageStorage;
    }


    /**
     * DI setter
     *
     * @param $configEmptyImage
     */
    public function setConfigEmptyImage(array $configEmptyImage)
    {
        $this->checkConfigEmptyImage($configEmptyImage);
        $this->configEmptyImage  = $configEmptyImage;
    }

    /**
     * @internal
     * @param $configEmptyImage
     */
    private function checkConfigEmptyImage($configEmptyImage)
    {
        $requireKeys = ['width' => true, 'height' => true, 'font' => true, 'fontSize' => true, 'text' => true];

        if (!empty(array_diff_key($configEmptyImage, $requireKeys))) {
            throw new InvalidStateException(sprintf("invalid input configEmptyImage %s, require %s", implode(",", $configEmptyImage), implode(",", array_keys($requireKeys))));
        }
    }


    /**
     * @return ImageJob
     */
    public function getImageJob(): ImageJob
    {
        if (null === $this->imageJob) {
            $this->imageJob = new ImageJob($this->imageManageStorage->getImageStorage(), $this->translator);
        }

        return $this->imageJob;
    }

    /**
     * @return PageImageJob
     */
    public function getPageImageJob(): PageImageJob
    {
        if (null === $this->pageImageJob) {
            $this->pageImageJob = new PageImageJob($this->imageManageStorage->getImageStorage(), $this->translator, $this->imageIdentifyRepository, $this->imageRepository);
        }

        return $this->pageImageJob;
    }





    /**
     * @return Image
     */
    public function generateEmptyImage($namespace): Image
    {
        $font     = $this->configEmptyImage['font'];
        $fontSize = $this->configEmptyImage['fontSize'];
        $width    = $this->configEmptyImage['width'];
        $height   = $this->configEmptyImage['height'];
        $text     = $this->configEmptyImage['text'] . PHP_EOL . "[$namespace]";

        list($lowLeftX, $lowLeftY, $lowRightX, $lowRightY, $highRightX, $highRightY, $highLeftX, $highLeftY) = imageftbbox($fontSize, 0, $font, $text);

        $textWidth  = $lowRightX - $lowLeftX;
        $textHeight = abs($lowLeftY) + abs($highRightY);

        $image = Image::fromBlank($width, $height, Image::rgb(200, 200, 200));
        $color = Image::rgb(155, 155, 255);
        $image->filledRectangle(10, 10, $width - 10, $height - 10, $color);
        $image->ttfText($fontSize, 0, ($width - $textWidth) / 2, ($height - $textHeight) / 2 - $highLeftY, Image::rgb(25, 15, 25), $font, $text);

        return $image;
    }



    /******************************************************************************************
     * getters / setters
     ******************************************************************************************/

    /**
     * @return ImageManageStorage
     */
    public function getImageManageStorage(): ImageManageStorage
    {
        return $this->imageManageStorage;
    }


    public function onRequest(Application $application)
    {
        $pageImageJob = $this->getPageImageJob();
        $this->imageManageStorage->callCreateImage = [$pageImageJob, 'updateImageFromIdentifier'];
    }


    function getSubscribedEvents()
    {
        return [
            'Nette\Application\Application::onRequest'
        ];

    }
}