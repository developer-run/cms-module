<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PageImageManageFacadeTest.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CatalogModule\Entities\ProductEntity;
use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\Tests\BaseTestCase;

class PageImageManageFacadeTest extends BaseTestCase
{

    const PATH = '/media';

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
        $www = $this->getContainer()->parameters['wwwDir'];
        $job = $this->imageManageFacade->getPageImageJob();

        $imageIdentifyRepository = $job->getImageIdentifyRepository();

        $testIdentifier = "test/image/test.jpg";
        if ($entities = $imageIdentifyRepository->findBy(['referenceIdentifier' => $testIdentifier])) {
            $imageIdentifyRepository->getEntityManager()->remove($entities)->flush();
        }

        $this->assertEquals(0, $imageIdentifyRepository->countBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found before create");

        $file        = $this->createUploadFile('image/jpeg');
        $imageEntity = $job->createImageFromUpload($file, $testIdentifier);


        $this->assertInstanceOf(ImagesEntity::class, $imageEntity);
        $this->em->persist($imageEntity)->flush();

        /** @var ImageIdentifyEntity $imageIDEntity */
        $this->assertNotNull($imageIDEntity = $imageIdentifyRepository->findBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier not found");

        $identifier = $imageEntity->getIdentifier();

        /*
         * test if imageFile is deleted after imageEntity removed
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");
        $this->em->remove($imageEntity)->flush();
        $this->assertFileNotExists($fileName);

        /*
         * remove identifier entity
         */
        $this->em->remove($imageIDEntity)->flush();
        $this->assertNull($imageIdentifyRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found");
    }


    /**
     * @return \Devrun\CmsModule\Entities\IImage|ImagesEntity
     */
    public function testCreateImageFromImage()
    {
        $www = $this->getContainer()->parameters['wwwDir'];
        $job = $this->imageManageFacade->getPageImageJob();

        $imageIdentifyRepository = $job->getImageIdentifyRepository();

        $testIdentifier = "test/image/test.png";

        if ($entities = $imageIdentifyRepository->findBy(['referenceIdentifier' => $testIdentifier])) {
            $imageIdentifyRepository->getEntityManager()->remove($entities)->flush();
        }

        $this->assertEquals(0, $imageIdentifyRepository->countBy(['referenceIdentifier' => $testIdentifier]), "image found before create");

        $file        = $this->createTestImage('image/png');
        $imageEntity = $job->createImageFromImage($file, $testIdentifier);


        $this->assertInstanceOf(ImagesEntity::class, $imageEntity);
        $this->em->persist($imageEntity)->flush();

        /** @var ImageIdentifyEntity $imageIDEntity */
        $this->assertNotNull($imageIDEntity = $imageIdentifyRepository->findBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier not found");

        $identifier = $imageEntity->getIdentifier();

        /*
         * test if imageFile is deleted after imageEntity removed
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");
//        $this->em->remove($imageEntity)->flush();
//        $this->assertFileNotExists($fileName);

        /*
         * remove identifier entity
         */
//        $this->em->remove($imageIDEntity)->flush();
//        $this->assertNull($imageIdentifyRepository->findOneBy(['referenceIdentifier' => $testIdentifier]), "image $testIdentifier found");

        return $imageEntity;
    }


    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromImage(ImagesEntity $imageEntity)
    {
        $this->markTestSkipped("reason: long time");

        $www = $this->getContainer()->parameters['wwwDir'];
        $job = $this->imageManageFacade->getPageImageJob();

        $origIdentifier = $imageEntity->getIdentifier();
        $origReference = $imageEntity->getReferenceIdentifier();

        /*
         * test if original file exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$origIdentifier");

        $file = $this->createTestImage('image/jpeg');  // updatujeme jpeg namísto png
        $imageEntity = $job->updateImageFromImage($imageEntity, $file);
        $this->em->flush();

        /*
         * original file must not exist
         */
        $this->assertFileNotExists($fileName = $www . self::PATH . "/$origIdentifier");

        $identifier = $imageEntity->getIdentifier();
        $reference = $imageEntity->getReferenceIdentifier();

        /*
         * new file must exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");

        $this->assertEquals($origReference, $reference);
        $this->assertNotEquals($origIdentifier, $identifier);

        return $imageEntity;
    }



    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromFileUpload(ImagesEntity $imageEntity)
    {
        $www = $this->getContainer()->parameters['wwwDir'];
        $job = $this->imageManageFacade->getPageImageJob();

        $origIdentifier = $imageEntity->getIdentifier();
        $origReference = $imageEntity->getReferenceIdentifier();

        /*
         * test if original file exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$origIdentifier");

        $file = $this->createUploadFile('image/png'); // updatujeme png namísto jpeg
        $imageEntity = $job->updateImageFromUpload($imageEntity, $file);
        $this->em->flush();

        /*
         * original file must not exist
         */
        $this->assertFileNotExists($fileName = $www . self::PATH . "/$origIdentifier");

        $identifier = $imageEntity->getIdentifier();
        $reference = $imageEntity->getReferenceIdentifier();

        /*
         * new file must exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");

        $this->assertEquals($origReference, $reference);
        $this->assertNotEquals($origIdentifier, $identifier);

        return $imageEntity;
    }



    /**
     * @depends testCreateImageFromImage
     */
    public function testUpdateImageFromIdentifier(ImagesEntity $imageEntity)
    {
        $www = $this->getContainer()->parameters['wwwDir'];
        $file   = $this->createTestImage('image/jpeg');
        $job    = $this->imageManageFacade->getPageImageJob();

        $dir = "$www/images/tests";
        $testFile = $dir . "/testIdentifier.jpeg";
        $identifier = "tests/testIdentifier.jpeg";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        file_put_contents($testFile, $file);

        /*
         * test if new file exist
         */
        $this->assertFileExists($testFile);

        $origIdentifier = $imageEntity->getIdentifier();
        $origReference = $imageEntity->getReferenceIdentifier();

        /*
         * original file exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$origIdentifier");


        $identifier = $imageEntity->getIdentifier();
        $imageEntity = $job->updateImageFromIdentifier($imageEntity, $identifier);

        /*
         * original file must not exist
         */
//        $this->assertFileNotExists($fileName = $www . self::PATH . "/$origIdentifier");

        $identifier = $imageEntity->getIdentifier();

        /*
         * new file must exist
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");


        $this->em->flush();
        return $imageEntity;
    }





    /**
     * @depends testCreateImageFromImage
     */
    public function testRemoveImage(ImagesEntity $imageEntity)
    {
        $www = $this->getContainer()->parameters['wwwDir'];
        $job = $this->imageManageFacade->getPageImageJob();

        $imageIdentifyRepository = $job->getImageIdentifyRepository();

        $this->assertNotNull($imageEntity->getId());

        $identifier = $imageEntity->getIdentifier();
        $referenceIdentifier = $imageEntity->getReferenceIdentifier();

        /*
         * test if imageFile is deleted after imageEntity removed
         */
        $this->assertFileExists($fileName = $www . self::PATH . "/$identifier");

        /*
         * remove identifier entity
         */
        $job->removeImage($imageEntity);
        $this->assertFileNotExists($fileName);

        $this->assertNull($imageIdentifyRepository->findOneBy(['referenceIdentifier' => $referenceIdentifier]), "image $referenceIdentifier found");
    }


}