<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImageFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Entities\IImage;
use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Facades\ImageJobs\ImageJob;
use Devrun\CmsModule\Facades\ImageJobs\PageImageJob;
use Devrun\CmsModule\Facades\ImageJobs\TranslatableImageJob;
use Devrun\CmsModule\Forms\IImageFormFactory;
use Devrun\CmsModule\Forms\IImagesFormFactory;
use Devrun\CmsModule\InvalidStateException;
use Devrun\CmsModule\Repositories\ImageIdentifyRepository;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Storage\ImageManageStorage;
use Devrun\Storage\ImageNameScript;
use Kdyby\Events\Subscriber;
use Kdyby\Monolog\Logger;
use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\DeprecatedException;
use Nette\Http\FileUpload;
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
     * new image from Image
     *
     * @param \Nette\Utils\Image $image
     * @param string             $identifier
     * @deprecated
     *
     * @return IImage|ImagesEntity
     */
    public function createImageFromImage(\Nette\Utils\Image $image, $identifier): ImagesEntity
    {
        throw new DeprecatedException("use new PageImageJob instead");

        if (!$imageIdentifyEntity = $this->imageIdentifyRepository->findOneBy(['referenceIdentifier' => $identifier])) {
            $imageIdentifyEntity = new ImageIdentifyEntity($identifier);
        }

        $imageEntity = new ImagesEntity($this->translator, $imageIdentifyEntity);

        $script = ImageNameScript::fromIdentifier($identifier);

        $imageEntity
            ->setName("{$script->name}.{$script->extension}");

        return $this->updateImageFromImage($imageEntity, $image);
    }


    /**
     * odstraní celou namespace obrázků z databáze i fyzicky
     *
     * @param null $namespace
     */
    public function removeNamespace($namespace, RouteEntity $routeEntity = null)
    {
        $findBy       = ['identify.namespace' => $namespace,];
        $imageStorage = $this->imageManageStorage->getImageStorage();

        if ($routeEntity) {
            $findBy['route'] = $routeEntity;
        }

        /** @var ImagesEntity[] $imageEntities */
        $imageEntities = $this->imageRepository->findBy($findBy);
        foreach ($imageEntities as $imageEntity) {
            if ($identifier = $imageEntity->getIdentifier()) {
                $imageStorage->delete($identifier);
            }
        }

        if ($imageEntities) $this->imageRepository->getEntityManager()->remove($imageEntities)->flush();
    }


    /**
     * odstraní jeden obrázek z databáze i fyzicky
     *
     * @param $id
     * @deprecated
     *
     * @return bool|null|object
     */
    public function removeImage($id)
    {
        throw new DeprecatedException("use new PageImageJob instead");

        if ($imageEntity = $this->imageRepository->find($id)) {

            try {
                $this->imageManageStorage->getImageStorage()->delete($imageEntity->getIdentifier());
                $this->imageRepository->getEntityManager()->remove($imageEntity)->flush();

            } catch (\Nette\InvalidStateException $e) {
                return false;
            }
        }

        return $imageEntity;
    }


    /**
     * aktualizuje obrázek a databázi
     *
     * @param ImagesEntity $imageEntity
     * @param FileUpload          $uploadImage
     * @deprecated
     */
    public function updateImageFromUpload( ImagesEntity $imageEntity, FileUpload $uploadImage)
    {
        throw new DeprecatedException("use new PageImageJob instead");

        $imageStorage = $this->imageManageStorage->getImageStorage();
        $imageStorage->delete($imageEntity->getIdentifier());

        $script = ImageNameScript::fromIdentifier($imageEntity->getIdentifier());
        $image  = $imageStorage->saveUpload($uploadImage, $script->namespace);

        $imageSize = $uploadImage->getImageSize();

        $imageEntity->setNamespace($script->namespace);

        $imageEntity->setName($image->name);
        $imageEntity->setAlt($image->name);
        $imageEntity->setSha($image->sha);
        $imageEntity->setIdentifier($image->identifier);
        $imageEntity->setPath($image->createLink());
        $imageEntity->setWidth($imageSize[0]);
        $imageEntity->setHeight($imageSize[1]);
        $imageEntity->setType($uploadImage->getContentType());

        $this->imageRepository->getEntityManager()->persist($imageEntity);
        $imageEntity->mergeNewTranslations();
    }


    /**
     * @param IImage $imageEntity
     * @param Image  $image
     * @deprecated
     *
     * @return IImage|ImagesEntity
     */
    public function updateImageFromImage( ImagesEntity $imageEntity, \Nette\Utils\Image $image)
    {
        throw new DeprecatedException("use new PageImageJob instead");

        // remove old image
        if ($identifier = $imageEntity->getIdentifier()) {
            $this->imageManageStorage->getImageStorage()->delete($identifier);
        }

        $referencePath = $imageEntity->getReferenceIdentifier();
        $ext = strtolower(pathinfo($referencePath, PATHINFO_EXTENSION));

        if ($ext == 'png') {
            $content = $image->toString($type = \Nette\Utils\Image::PNG);

        } elseif (in_array($ext, ['jpeg', 'jpg'])) {
            $content = $image->toString($type = \Nette\Utils\Image::JPEG);

        } elseif ($ext == 'gif') {
            $content = $image->toString($type = \Nette\Utils\Image::GIF);

        } else {
            $content = $image->toString();
            $type = \Nette\Utils\Image::JPEG;
        }

        $imageSave  = $this->imageManageStorage->getImageStorage()->saveContent($content, $imageEntity->getName(), $imageEntity->getNamespace());
        $script = ImageNameScript::fromIdentifier($imageSave->identifier);

        $imageEntity->setNamespace($script->namespace);

        $imageEntity->setName($imageSave->name);
        $imageEntity->setAlt($imageSave->name);
        $imageEntity->setSha($imageSave->sha);
        $imageEntity->setIdentifier($imageSave->identifier);
        $imageEntity->setPath($imageSave->createLink());
        $imageEntity->setWidth($image->getWidth());
        $imageEntity->setHeight($image->getHeight());
        $imageEntity->setType(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $imageSave->createLink()));

        $this->imageRepository->getEntityManager()->persist($imageEntity);
        $imageEntity->mergeNewTranslations();

        return $imageEntity;
    }


    /**
     * new image from template macro
     * <img n:image="'images/namespace/name.png', '200x200', shrink_only">
     *
     *
     * @param ImagesEntity $imageEntity
     * @param              $identifier
     * @deprecated
     *
     * @return ImagesEntity
     */
    public function updateImageFromIdentifier(ImagesEntity $imageEntity, $identifier)
    {
        throw new DeprecatedException("use new PageImageJob instead");

        if ($identifier && file_exists($identifier)) {

            // remove old image
            if ($imageEntity->getIdentifier()) {
                $this->imageManageStorage->getImageStorage()->delete($imageEntity->getIdentifier());
            }

            $script    = ImageNameScript::fromIdentifier($identifier);
            $content   = file_get_contents($identifier);
            $fileName  = implode('.', [$script->name, $script->extension]);
            $namespace = implode('/', [$script->namespace, $script->prefix]);

            $img       = \Nette\Utils\Image::fromFile($identifier);
            $image     = $this->imageManageStorage->getImageStorage()->saveContent($content, $fileName, $namespace);
            $scriptNew = ImageNameScript::fromIdentifier($image->identifier);

            $imageEntity->getIdentify()->setReferenceIdentifier($identifier);
            $imageEntity->getIdentify()->setName($script->name);
            $imageEntity->getIdentify()->setNamespace($scriptNew->namespace);

            $imageEntity->setName($image->name);
            $imageEntity->setAlt($image->name);
            $imageEntity->setSha($image->sha);
            $imageEntity->setIdentifier($image->identifier);
            $imageEntity->setPath($image->createLink());
            $imageEntity->setWidth($img->getWidth());
            $imageEntity->setHeight($img->getHeight());
            $imageEntity->setType(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $image->createLink()));

            $this->imageRepository->getEntityManager()->persist($imageEntity);
            $imageEntity->mergeNewTranslations();
        }

        return $imageEntity;
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
//        $this->imageManageStorage->callCreateImage = $this->updateImageFromIdentifier;
        $pageImageJob = $this->getPageImageJob();


        $this->imageManageStorage->callCreateImage = $pageImageJob->updateImageFromIdentifier;
    }


    function getSubscribedEvents()
    {
        return [
            'Nette\Application\Application::onRequest'
        ];

    }
}