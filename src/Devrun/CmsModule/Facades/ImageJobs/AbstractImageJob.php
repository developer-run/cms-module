<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    AbstractImageJob.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\ImageJobs;

use Devrun\CmsModule\Entities\IImage;
use Devrun\Storage\ImageStorage;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Http\FileUpload;
use Nette\Utils\Image;

/**
 * Class AbstractImageJob
 *
 * @package Devrun\CmsModule\Facades\ImageJobs
 * @method callCreateImageEntity($identifier)
 */
abstract class AbstractImageJob
{

    /** @var ImageStorage */
    protected $imageStorage;

    /** @var Translator */
    protected $translator;

    /** @var EntityRepository */
    private $imageRepository;


    /** @var Callback */
    public $callCreateImageEntity;


    /**
     * AbstractImageJob constructor.
     *
     * @param ImageStorage $imageStorage
     */
    public function __construct(ImageStorage $imageStorage, Translator $translator)
    {
        $this->imageStorage = $imageStorage;
        $this->translator   = $translator;
    }


    /**
     * new image entity from Image
     *
     * @param Image  $image
     * @param string $identifier
     *
     * @return IImage
     */
    abstract public function createImageFromImage(Image $image, $identifier): IImage;


    /**
     * new image entity from FileUpload
     *
     * @param FileUpload $image
     * @param string     $identifier
     *
     * @return IImage
     */
    abstract public function createImageFromUpload(FileUpload $image, $identifier): IImage;


    /**
     * update image entity from Image
     *
     * @param IImage $imageEntity
     * @param Image  $image
     *
     * @return IImage
     */
    abstract public function updateImageFromImage(IImage $imageEntity, Image $image): IImage;


    /**
     * aktualizuje obrázek a databázi
     *
     * @param IImage     $imageEntity
     * @param FileUpload $uploadImage
     *
     * @return IImage
     */
    abstract public function updateImageFromUpload(IImage $imageEntity, FileUpload $uploadImage): IImage;


    /**
     * new image from template macro
     * <img n:image="'images/namespace/name.png', '200x200', shrink_only">
     *
     * @param IImage $imageEntity
     * @param string $identifier
     *
     * @return IImage
     */
    abstract public function updateImageFromIdentifier(IImage $imageEntity, string $identifier): IImage;




    /**
     * odstraní celou namespace obrázků z databáze i fyzicky
     *
     * @param $namespace
     *
     * @return mixed
     */
    abstract public function removeNamespace($namespace);




    /**
     * odstraní jeden obrázek z databáze i fyzicky
     *
     * @param $id
     *
     * @return bool|null|object
     */
    public function removeImageId($id)
    {
        if (($imageEntity = $this->imageRepository->find($id)) && $imageEntity instanceof IImage) {
            return $this->removeImage($imageEntity);
        }

        return $imageEntity;
    }


    /**
     * odstraní jeden obrázek z databáze i fyzicky
     *
     * @param IImage $imageEntity
     *
     * @return bool|IImage
     */
    public function removeImage(IImage $imageEntity)
    {
        try {
            $this->getImageStorage()->delete($imageEntity->getIdentifier());
            $this->getImageRepository()->getEntityManager()->remove($imageEntity)->flush();

        } catch (\Nette\InvalidStateException $e) {
            return false;
        }

        return $imageEntity;
    }


    /**
     * @return ImageStorage
     */
    protected function getImageStorage(): ImageStorage
    {
        return $this->imageStorage;
    }


    /**
     * @param EntityRepository $imageRepository
     *
     * @return $this
     */
    public function setImageRepository(EntityRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
        return $this;
    }


    protected function getImageRepository()
    {
        if (!$this->imageRepository) {
            throw new InvalidStateException("setImageRepository() first");
        }

        return $this->imageRepository;
    }


}