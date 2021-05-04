<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    PageImageJob.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\ImageJobs;

use Devrun\CmsModule\Entities\IImage;
use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\InvalidArgumentException;
use Devrun\CmsModule\Repositories\ImageIdentifyRepository;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\Storage\ImageNameScript;
use Devrun\Storage\ImageStorage;
use Devrun\Utils\Debugger;
use Kdyby\Translation\Translator;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;

class PageImageJob extends AbstractImageJob
{
    use SmartObject;


    /** @var ImageIdentifyRepository */
    private $imageIdentifyRepository;

    /** @var ImageRepository */
    private $imageRepository;


    /**
     * PageImageJob constructor.
     *
     * @param ImageStorage            $imageStorage
     * @param Translator              $translator
     * @param ImageIdentifyRepository $imageIdentifyRepository
     */
    public function __construct(ImageStorage $imageStorage, Translator $translator, ImageIdentifyRepository $imageIdentifyRepository, ImageRepository $imageRepository)
    {
        parent::__construct($imageStorage, $translator);

        $this->imageRepository         = $imageRepository;
        $this->imageIdentifyRepository = $imageIdentifyRepository;
    }

    /*
     * -----------------------------------------------------------------------------
     * getters
     * -----------------------------------------------------------------------------
     */

    /**
     * @return ImageIdentifyRepository
     */
    public function getImageIdentifyRepository(): ImageIdentifyRepository
    {
        return $this->imageIdentifyRepository;
    }

    /**
     * @return ImageRepository
     */
    public function getImageRepository(): ImageRepository
    {
        return $this->imageRepository;
    }

    /* _____________________________________________________________________________
     * getters
     * _____________________________________________________________________________
     */





    /**
     * new image entity from FileUpload
     *
     * @param FileUpload $image
     * @param string     $identifier
     *
     * @return IImage
     */
    public function createImageFromUpload(FileUpload $image, $identifier): IImage
    {
        if (!$imageIdentifyEntity = $this->imageIdentifyRepository->findOneBy(['referenceIdentifier' => $identifier])) {
            $imageIdentifyEntity = new ImageIdentifyEntity($identifier);
        }

        $imageEntity = new ImagesEntity($this->translator, $imageIdentifyEntity);

        $script = ImageNameScript::fromIdentifier($identifier);

        $imageEntity->setName("{$script->name}.{$script->extension}");

        return $this->updateImageFromUpload($imageEntity, $image);

    }



    /**
     * new image entity from Image
     *
     * @param \Nette\Utils\Image $image
     * @param string             $identifier
     *
     * @return IImage|ImagesEntity
     */
    public function createImageFromImage(\Nette\Utils\Image $image, $identifier): IImage
    {
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
     * update image entity from Image
     *
     * @param IImage|ImagesEntity $imageEntity
     * @param ImagesEntity|Image  $image
     *
     * @return IImage|ImagesEntity
     */
    public function updateImageFromImage(IImage $imageEntity, Image $image): IImage
    {
        if (!$imageEntity instanceof ImagesEntity) {
            throw new InvalidArgumentException("ImagesEntity is required");
        }

        // remove old image
        if ($identifier = $imageEntity->getIdentifier()) {
            $this->getImageStorage()->delete($identifier);
        }

        $referencePath = $imageEntity->getReferenceIdentifier();
        $ext           = strtolower(pathinfo($referencePath, PATHINFO_EXTENSION));

        if ($ext == 'png') {
            $content = $image->toString($type = \Nette\Utils\Image::PNG);

        } elseif (in_array($ext, ['jpeg', 'jpg'])) {
            $content = $image->toString($type = \Nette\Utils\Image::JPEG);

        } elseif ($ext == 'gif') {
            $content = $image->toString($type = \Nette\Utils\Image::GIF);

        } else {
            $content = $image->toString();
            $type    = \Nette\Utils\Image::JPEG;
        }

        $imageSave = $this->getImageStorage()->saveContent($content, $imageEntity->getName(), $imageEntity->getNamespace());
        $script    = ImageNameScript::fromIdentifier($imageSave->identifier);

        $imageEntity->setNamespace($script->namespace);

        $imageEntity->setName($imageSave->name);
        $imageEntity->setAlt($imageSave->name);
        $imageEntity->setSha($imageSave->sha);
        $imageEntity->setIdentifier($imageSave->identifier);
        $imageEntity->setPath($imageSave->createLink());
        $imageEntity->setWidth($image->getWidth());
        $imageEntity->setHeight($image->getHeight());

        $file = $this->getImageStorage()->getWwwDir() . DIRECTORY_SEPARATOR . $imageSave->createLink();
        $imageEntity->setType(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));

        $this->imageRepository->getEntityManager()->persist($imageEntity);
        $imageEntity->mergeNewTranslations();

        return $imageEntity;

    }


    /**
     * aktualizuje obrázek a databázi
     *
     * @param IImage|ImagesEntity $imageEntity
     * @param FileUpload $uploadImage
     *
     * @return IImage
     */
    public function updateImageFromUpload(IImage $imageEntity, FileUpload $uploadImage): IImage
    {
        if (!$imageEntity instanceof ImagesEntity) {
            throw new InvalidArgumentException("ImagesEntity is required");
        }

        $imageStorage = $this->getImageStorage();
        if ($identifier = $imageEntity->getIdentifier()) {
            $imageStorage->delete($identifier);
        }

        $image     = $imageStorage->saveUpload($uploadImage, $imageEntity->getNamespace());
        $imageSize = $uploadImage->getImageSize();

        $imageEntity->setName($image->name);
        $imageEntity->setAlt($image->name);
        $imageEntity->setSha($image->sha);
        $imageEntity->setIdentifier($image->identifier);
        $imageEntity->setPath($image->createLink());
        $imageEntity->setWidth($imageSize[0]);
        $imageEntity->setHeight($imageSize[1]);
        $imageEntity->setType($uploadImage->getContentType());

        $this->getImageRepository()->getEntityManager()->persist($imageEntity);
        $imageEntity->mergeNewTranslations();

        return $imageEntity;
    }


    /**
     * new image from template macro
     * <img n:image="'images/namespace/name.png', '200x200', shrink_only">
     *
     * @param IImage|ImagesEntity $imageEntity
     * @param string $identifier
     *
     * @return IImage
     */
    public function updateImageFromIdentifier(IImage $imageEntity, string $identifier): IImage
    {
        if ($identifier && file_exists($identifier)) {
            $imageStorage = $this->getImageStorage();

            // remove old image
            if ($imageEntity->getIdentifier()) {
                $imageStorage->delete($identifier);
            }

            $script    = ImageNameScript::fromIdentifier($identifier);
            $content   = file_get_contents($identifier);
            $fileName  = implode('.', [$script->name, $script->extension]);
            $namespace = implode('/', [$script->namespace, $script->prefix]);

            try {
                $img = \Nette\Utils\Image::fromFile($identifier);
            } catch (UnknownImageFileException $e) {
                return $imageEntity;
            }

            $image     = $imageStorage->saveContent($content, $fileName, $namespace);
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
     * remove all images which have some identify
     *
     * @param IImage|ImagesEntity $imageEntity
     *
     * @return bool|IImage|ImagesEntity
     */
    public function removeAllImagesFromIdentify(IImage $imageEntity)
    {
        if (!$imageEntity instanceof ImagesEntity) {
            throw new InvalidArgumentException("ImagesEntity is required");
        }

        $imageStorage = $this->getImageStorage();
        if ($identifier = $imageEntity->getIdentifier()) {
            $imageStorage->delete($identifier);
        }

        $this->getImageIdentifyRepository()->getEntityManager()->remove($imageEntity->getIdentify())->flush();
        return $imageEntity;
    }


    /**
     * remove all images which have some identify
     *
     * @param $id
     * @return bool|object|null
     * @throws \Exception
     */
    public function removeAllImagesFromIdentifyByImageID($id)
    {
        if ($imageEntity = $this->imageRepository->find($id)) {

            try {
                $imageStorage = $this->getImageStorage();
                if ($identifier = $imageEntity->getIdentifier()) {
                    $imageStorage->delete($identifier);
                }

                $this->getImageIdentifyRepository()->getEntityManager()->remove($imageEntity->getIdentify())->flush();

            } catch (\Nette\InvalidStateException $e) {
                return false;
            }
        }

        return $imageEntity;
    }



    /**
     * odstraní celou namespace obrázků z databáze i fyzicky
     *
     * @param $namespace
     *
     * @return mixed
     */
    public function removeNamespace($namespace)
    {
        // TODO: Implement removeNamespace() method.
    }


}