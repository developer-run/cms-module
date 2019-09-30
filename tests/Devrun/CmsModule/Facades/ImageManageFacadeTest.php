<?php
/**
 * This file is part of nivea-2019-klub-rewards.
 * Copyright (c) 2019
 *
 * @file    ImageManageFacadeTest.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CatalogModule\Entities\ProductEntity;
use Devrun\CatalogModule\Entities\ProductImageEntity;
use Devrun\CmsModule\Entities\IImage;
use Devrun\Tests\BaseTestCase;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;

/**
 * Class ImageManageFacadeTest
 *
 * @package Devrun\CmsModule\Facades
 * @codeCoverageIgnore
 */
class ImageManageFacadeTest extends BaseTestCase
{

    /** @var \Devrun\CmsModule\Facades\ImageManageFacade @inject */
    public $imageManageFacade;

    /** @var \Devrun\CmsModule\CatalogModule\Facades\CatalogFacade @inject */
    public $catalogFacade;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;

    /** @var \Kdyby\Doctrine\EntityManager @inject */
    public $em;



//    public static $initDatabase = true;
    public static $initDatabase = false;




    private function createNewProduct()
    {
        $productEntity = new ProductEntity($this->translator, 'testovaci Upload');
        $productEntity->mergeNewTranslations();
        $this->catalogFacade->getProductRepository()->getEntityManager()->persist($productEntity)->flush();
        return $productEntity;
    }



    /**
     * vytvoří produkt a obraz z UploadFile, pak smaže produkt
     */
    public function testCreateImageFromUpload()
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($imageRepository = $this->catalogFacade->getProductImageRepository());

        $productEntity = $this->createNewProduct();
        $testIdentifier = "products/{$productEntity->getId()}/test.jpg";

        $this->assertNull($imageRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found before create");

        $job->callCreateImageEntity = function ($identifier) use ($testIdentifier, $productEntity) {

            $this->assertSame($testIdentifier, $identifier);

            $entity = new ProductImageEntity($productEntity, $identifier);
            return $entity;
        };

        $file = $this->createUploadFile('image/jpeg');
        $imageEntity = $job->createImageFromUpload($file, $testIdentifier);

        $this->assertInstanceOf(ProductImageEntity::class, $imageEntity);
        $this->em->persist($imageEntity)->flush();

        $this->assertNotNull($imageRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier not found");
        $this->em->remove($productEntity)->flush();

        $this->assertNull($imageRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found");
    }


    /**
     * vytvoří produkt a obraz z Image
     * @return IImage
     */
    public function testCreateImageFromImage()
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($imageRepository = $this->catalogFacade->getProductImageRepository());

        $productEntity = $this->createNewProduct();
        $testIdentifier = "products/{$productEntity->getId()}/test.jpg";

        $this->em->persist($productEntity)->flush();

        $this->assertNull($findImageEntity = $imageRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found before create");

        $job->callCreateImageEntity = function ($identifier) use ($testIdentifier, $productEntity) {

            $this->assertSame($testIdentifier, $identifier);

            $entity = new ProductImageEntity($productEntity, $identifier);
            return $entity;
        };

        $file = $this->createTestImage('image/jpeg');

        $imageEntity = $job->createImageFromImage($file, $testIdentifier);

        $this->assertInstanceOf(ProductImageEntity::class, $imageEntity);
        $this->em->persist($imageEntity)->flush();

        return $imageEntity;
    }


    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromImage(ProductImageEntity $imageEntity)
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($this->catalogFacade->getProductImageRepository());

        $origIdentifier = $imageEntity->getIdentifier();
        $origReference = $imageEntity->getReferenceIdentifier();

        $file = $this->createTestImage('image/png');
        $imageEntity = $job->updateImageFromImage($imageEntity, $file);
        $this->em->persist($imageEntity)->flush();

        $identifier = $imageEntity->getIdentifier();
        $reference = $imageEntity->getReferenceIdentifier();

        $this->assertEquals($origReference, $reference);
        $this->assertNotEquals($origIdentifier, $identifier);

        return $imageEntity;
    }


    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromFileUpload(ProductImageEntity $imageEntity)
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($this->catalogFacade->getProductImageRepository());

        $origIdentifier = $imageEntity->getIdentifier();
        $origReference = $imageEntity->getReferenceIdentifier();

        $file = $this->createUploadFile('image/jpeg');
        $imageEntity = $job->updateImageFromUpload($imageEntity, $file);
        $this->em->persist($imageEntity)->flush();

        $identifier = $imageEntity->getIdentifier();
        $reference = $imageEntity->getReferenceIdentifier();

        $this->assertEquals($origReference, $reference);
        $this->assertNotEquals($origIdentifier, $identifier);

        return $imageEntity;
    }


    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromIdentifier(ProductImageEntity $imageEntity)
    {
        $wwwDir = $this->getContainer()->parameters['wwwDir'];
        $file = $this->createTestImage('image/jpeg');

        $dir = "$wwwDir/images/tests";
        $testFile = $dir . "/test.jpeg";
        $identifier = "tests/test.jpeg";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        file_put_contents($testFile, $file);

        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($this->catalogFacade->getProductImageRepository());


        $imageEntity = $job->updateImageFromIdentifier($imageEntity, $identifier);

        $this->imageManageFacade->imageRepository->getEntityManager()->persist($imageEntity)->flush();

        return $imageEntity;
    }


    /**
     * @depends testUpdateImageFromIdentifier
     */
    public function testRemoveNamespace()
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($this->catalogFacade->getProductImageRepository());

//        $imageEntities = $job->removeNamespace($identifier = 'background/wood.jpg');
        $imageEntities = $job->removeNamespace($identifier = 'projects/1');

        $this->imageManageFacade->imageRepository->getEntityManager()->flush();
    }


    /**
     * @depends testCreateImageFromImage
     */
    public function testRemoveImage(ProductImageEntity $imageEntity)
    {
        $job = $this->imageManageFacade->getImageJob();
        $job->setImageRepository($this->catalogFacade->getProductImageRepository());

        $this->assertNotNull($imageEntity->getId());
        $imageEntity = $job->removeImage($imageEntity);
        $this->assertNull($imageEntity->id);
    }





}
