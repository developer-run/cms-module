<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ImageJob.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\ImageJobs;

use Devrun\CmsModule\Entities\IImage;
use Devrun\DoctrineModule\Entities\Attributes\Translation;
use Devrun\Storage\ImageNameScript;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Reflection;

class ImageJob extends AbstractImageJob
{


    /**
     * new image entity from Image
     *
     * @param \Nette\Utils\Image $image
     * @param string             $identifier
     *
     * @return IImage
     */
    public function createImageFromImage(\Nette\Utils\Image $image, $identifier): IImage
    {
        if (!$imageEntity = $this->getImageRepository()->findOneBy(['referenceIdentifier' => $identifier])) {
            if (!$this->callCreateImageEntity) {
                throw new InvalidStateException("set callCreateImageEntity() for create new image entity callback");
            }

            $imageEntity = call_user_func($this->callCreateImageEntity, $identifier);
        }

        $script = ImageNameScript::fromIdentifier($identifier);

        $imageEntity
            ->setName("{$script->name}.{$script->extension}");

        return $this->updateImageFromImage($imageEntity, $image);
    }

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
        if (!$imageEntity = $this->getImageRepository()->findOneBy(['referenceIdentifier' => $identifier])) {
            if (!$this->callCreateImageEntity) {
                throw new InvalidStateException("set callCreateImageEntity() for create new image entity callback");
            }

            $imageEntity = call_user_func($this->callCreateImageEntity, $identifier);
        }

        $script = ImageNameScript::fromIdentifier($identifier);

        $imageEntity
            ->setName("{$script->name}.{$script->extension}");

        return $this->updateImageFromUpload($imageEntity, $image);
    }





    /**
     * update image entity from Image
     *
     * @param IImage $imageEntity
     * @param Image  $image
     *
     * @return IImage
     */
    public function updateImageFromImage(IImage $imageEntity, Image $image): IImage
    {
        // remove old image
        $imageStorage = $this->getImageStorage();
        if ($identifier = $imageEntity->getIdentifier()) {
            $imageStorage->delete($identifier);
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

        $imageSave  = $imageStorage->saveContent($content, $imageEntity->getName(), $imageEntity->getNamespace());
        $script = ImageNameScript::fromIdentifier($imageSave->identifier);

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

        $this->getImageRepository()->getEntityManager()->persist($imageEntity);

        // check and call translation method
        if (method_exists($imageEntity, 'mergeNewTranslations')) {
            $imageEntity->mergeNewTranslations();
        }

        return $imageEntity;
    }


    /**
     * aktualizuje obrázek a databázi
     *
     * @param IImage     $imageEntity
     * @param FileUpload $uploadImage
     *
     * @return IImage
     */
    public function updateImageFromUpload(IImage $imageEntity, FileUpload $uploadImage): IImage
    {
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

        // check and call translation method
        if (method_exists($imageEntity, 'mergeNewTranslations')) {
            $imageEntity->mergeNewTranslations();
        }

        return $imageEntity;
    }


    /**
     * new image from template macro
     * <img n:image="'images/namespace/name.png', '200x200', shrink_only">
     *
     * @param IImage $imageEntity
     * @param string $identifier
     *
     * @return IImage
     */
    public function updateImageFromIdentifier(IImage $imageEntity, string $identifier): IImage
    {
        if ($identifier && file_exists($identifier)) {

            // remove old image
            $imageStorage = $this->getImageStorage();
            if ($identifier = $imageEntity->getIdentifier()) {
                $imageStorage->delete($identifier);
            }

            $script    = ImageNameScript::fromIdentifier($identifier);
            $content   = file_get_contents($identifier);
            $fileName  = implode('.', [$script->name, $script->extension]);
            $namespace = implode('/', [$script->namespace, $script->prefix]);

            $img       = \Nette\Utils\Image::fromFile($identifier);
            $image     = $imageStorage->saveContent($content, $fileName, $namespace);
            $scriptNew = ImageNameScript::fromIdentifier($image->identifier);

            $imageEntity->setName($image->name);
            $imageEntity->setAlt($image->name);
            $imageEntity->setSha($image->sha);
            $imageEntity->setIdentifier($image->identifier);
            $imageEntity->setPath($image->createLink());
            $imageEntity->setWidth($img->getWidth());
            $imageEntity->setHeight($img->getHeight());

            $file = $this->getImageStorage()->getWwwDir() . DIRECTORY_SEPARATOR . $image->createLink();
            $imageEntity->setType(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));

            $this->getImageRepository()->getEntityManager()->persist($imageEntity);

            // check and call translation method
            if (method_exists($imageEntity, 'mergeNewTranslations')) {
                $imageEntity->mergeNewTranslations();
            }
        }

        return $imageEntity;
    }


    /**
     * odstraní celou namespace obrázků z databáze i fyzicky
     *
     * @param $namespace
     *
     * @return IImage[]
     */
    public function removeNamespace($namespace, ...$arguments)
    {
        $findBy       = ['namespace' => $namespace,];
//        $findBy       = ['identify.namespace' => $namespace,];
        $imageStorage = $this->getImageStorage();

//        if ($routeEntity) {
//            $findBy['route'] = $routeEntity;
//        }

        /** @var IImage[] $imageEntities */
        $imageEntities = $this->getImageRepository()->findBy($findBy);
        foreach ($imageEntities as $imageEntity) {
            if ($identifier = $imageEntity->getIdentifier()) {
                $imageStorage->delete($identifier);
            }
        }

        if ($imageEntities) $this->getImageRepository()->getEntityManager()->remove($imageEntities);
        return $imageEntities;
    }




}