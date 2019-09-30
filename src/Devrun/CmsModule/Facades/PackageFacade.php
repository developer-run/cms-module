<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PackageFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\ImagesTranslationEntity;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Devrun\CmsModule\Facades\Package\PackageDomain;
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\CmsModule\Routes\PageRoute;
use Devrun\Doctrine\Entities\UserEntity;
use Devrun\Facades\ImageFacade;
use Devrun\Security\LoggedUser;
use Devrun\Storage\ImageNameScript;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Monolog\Logger;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\Image;
use Tracy\Debugger;

/**
 * Class PackageFacade
 *
 * @package Devrun\CmsModule\Facades
 * @method onAfterCopyPackageRoute(PackageEntity $newPackage, RouteEntity $newRouteEntity)
 * @method onCopyPackage(PackageEntity $newPackage, PackageEntity $oldPackage)
 * @method onRemovePackage(PackageEntity $package)
 * @method onChangePackage(PackageEntity $package)
 * @method onCopyRoute(RouteEntity $newRouteEntity, RouteEntity $oldRouteEntity, PackageEntity $newPackage, PackageEntity $oldPackage = null)
 */
class PackageFacade
{

    const EVENT_COPY_PACKAGE = 'Devrun\CmsModule\Facades\PackageFacade::onCopyPackage';
    const EVENT_CHANGE_PACKAGE = 'Devrun\CmsModule\Facades\PackageFacade::onChangePackage';
    const EVENT_REMOVE_PACKAGE = 'Devrun\CmsModule\Facades\PackageFacade::onRemovePackage';
    const EVENT_COPY_ROUTE = 'Devrun\CmsModule\Facades\PackageFacade::onCopyRoute';


    use SmartObject;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var ImageManageFacade */
    private $imageManageFacade;

    /** @var EntityManager */
    private $em;

    /** @var Logger */
    private $logger;

    /** @var UserEntity */
    private $userEntity;

    /** @var string DI setting www path */
    private $wwwDir;

    /** @var array event on after copy new route */
    public $onAfterCopyPackageRoute = [];

    /** @var array event on copy package */
    public $onCopyPackage = [];

    /** @var array event on change package, etc edit domain ... */
    public $onChangePackage = [];

    /** @var array event on delete package */
    public $onRemovePackage = [];

    /** @var array event on copy route */
    public $onCopyRoute = [];



    /**
     * PackageFacade constructor.
     *
     * @param PackageRepository $packageRepository
     */
    public function __construct($wwwDir, PackageRepository $packageRepository, ImageManageFacade $imageManageFacade, LoggedUser $loggedUser, Logger $logger)
    {
        $this->wwwDir = $wwwDir;
        $this->packageRepository = $packageRepository;
        $this->imageManageFacade = $imageManageFacade;
        $this->logger = $logger;
        $this->em = $packageRepository->getEntityManager();
        $this->userEntity = $loggedUser->getUserEntity();
    }

    /**
     * @return PackageRepository
     */
    public function getPackageRepository(): PackageRepository
    {
        return $this->packageRepository;
    }


    /**
     * @param PackageEntity      $newPackage
     * @param PackageEntity|null $oldPackage
     */
    public function copyPackage(PackageEntity & $newPackage, PackageEntity $oldPackage = null)
    {
        $this->checkPackagesForCopy($newPackage, $oldPackage);

        $this->onCopyRoute[] = function (RouteEntity $newRouteEntity, RouteEntity $oldRouteEntity, PackageEntity $newPackage, PackageEntity $oldPackage = null) {
            $this->copyImages($newRouteEntity, $oldRouteEntity, $newPackage, $oldPackage);
        };

        /*
         * readyToFlush = any route to copy, can do anything
         */
        $readyToFlush = $this->copyPackageRoutes($newPackage, $oldPackage);

        if ($readyToFlush) {

            /*
             * onCopyPackage call onCopyRoute[]
             */
            $this->onCopyPackage($newPackage, $oldPackage);
            $this->em->flush();
        }
    }



    protected function copyImages(RouteEntity $newRouteEntity, RouteEntity $oldRouteEntity, PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        $imageRepository = $this->imageManageFacade->imageRepository;
        $query = $imageRepository->getQuery()
            ->withRoute()
            ->withIdentify()
            ->withTranslations()
            ->byRoute($oldRouteEntity)
            ->byPackage($oldPackage);


        /** @var ImagesEntity[] $oldImageEntities */
        $oldImageEntities = $imageRepository->fetch($query);

        foreach ($oldImageEntities as $oldImageEntity) {

            /** @var ImagesTranslationEntity[] $oldImageTranslations */
            $oldImageTranslations = $oldImageEntity->getTranslations();
            $newImageEntity       = clone $oldImageEntity;
            $newImageEntity->setRoute($newRouteEntity);
            $newImageEntity->setInserted(null);
            $newImageEntity->setUpdated(null);

            foreach ($oldImageTranslations as $oldImageTranslation) {
                $newImageTranslation = clone $oldImageTranslation;
                $fileName = $this->wwwDir . DIRECTORY_SEPARATOR . $newImageTranslation->getPath();

                if (file_exists($fileName)) {
                    $image  = file_get_contents($fileName);
                    $newImg = $this->imageManageFacade->getImageManageStorage()->getImageStorage()->saveContent($image, $newImageTranslation->getName(), $oldImageEntity->getNamespace());
                    $script = ImageNameScript::fromIdentifier($newImg->identifier);

                    $newImageTranslation
                        ->setIdentifier($newImg->identifier)
                        ->setSha($newImg->sha)
                        ->setPath($path = $newImg->createLink());
                        //->setName(basename($path));
                        //->setAlt(basename($path));
                }

                $newImageEntity->addTranslation($newImageTranslation);
            }

            $this->em->persist($newImageEntity);
        }

    }



    /**
     * check source and destination module
     * check new package id, must exist
     *
     * @param PackageEntity      $newPackage
     * @param PackageEntity|null $oldPackage
     */
    private function checkPackagesForCopy(PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        $newPackage->setUser($this->userEntity);

        if ($oldPackage) {
            $newPackage
                ->setModule($module = $oldPackage->getModule())
                ->setThemeVariables($oldPackage->getThemeVariables());
        }

        /*
         * insert new package
         * warning, parent must try catch UniqueConstraintViolationException !!!
         */
        if ($newPackage->getId() == null) {
            $this->em->persist($newPackage)->flush();
            $packageId = $newPackage->getId();
        }

    }



    /**
     * @param PackageEntity      $newPackage
     * @param PackageEntity|null $oldPackage
     *
     * @return bool
     */
    protected function copyPackageRoutes(PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        $module    = $newPackage->getModule();
        $packageId = $newPackage->getId();

        if ($oldRouteEntities = $this->packageRepository->getSourceRoutes($newPackage, $oldPackage)) {
            /** @var RouteEntity[] $oldRouteEntities */
            foreach ($oldRouteEntities as $oldRouteEntity) {
                $oldRouteTranslations = $oldRouteEntity->getTranslations();
                $newRouteEntity       = clone $oldRouteEntity;
                $newRouteEntity->setPackage($newPackage);
                $newRouteEntity->setInserted(null);
                $newRouteEntity->setUpdated(null);

                foreach ($oldRouteTranslations as $oldRouteTranslation) {
                    $newTranslationEntity = clone $oldRouteTranslation;
                    $newUrl = $this->generateUniquePackageUrl($newPackage, $oldRouteEntity, $newTranslationEntity);
                    $fixNewUrl = null;
                    $newUrlInc = 1;

                    // is newUrl in database?
                    // generate newUrl with postfix
                    $countNewUrl = $this->em->getRepository(RouteTranslationEntity::class)->countBy(['url' => $newUrl]);
                    while ($countNewUrl > 0) {
                        $fixNewUrl = $newUrl . "_" . $newUrlInc++;
                        $countNewUrl = $this->em->getRepository(RouteTranslationEntity::class)->countBy(['url' => $fixNewUrl]);
                    }

                    $newTranslationEntity->setUrl($fixNewUrl ? $fixNewUrl : $newUrl);
                    $newRouteEntity->addTranslation($newTranslationEntity);
                }

                $params = $this->mergeRouteParameters($newRouteEntity, ['package' => $packageId]);
                $newRouteEntity->setParams($params);

                $this->onAfterCopyPackageRoute($newPackage, $newRouteEntity); // event for maybe modify $newRouteEntity
                $this->onCopyRoute($newRouteEntity, $oldRouteEntity, $newPackage, $oldPackage);

    //            dump($newRouteEntity);

                $this->em->persist($newRouteEntity);
            }
        }

//        $this->em->flush();
        return !empty($oldRouteEntities);
    }


    /**
     * Returns route sorted params
     *
     * @param RouteEntity $routeEntity
     * @param array       $params
     *
     * @return array
     */
    public function mergeRouteParameters(RouteEntity $routeEntity, array $params = [])
    {
        $params = array_unique(array_merge($routeEntity->getParams(), $params));
        ksort($params);
        return $params;
    }





    /**
     * @param PackageEntity          $package
     * @param RouteEntity            $route
     * @param RouteTranslationEntity $translationEntity
     *
     * @return string
     */
    private function generateUniquePackageUrl(PackageEntity $package, RouteEntity $route, RouteTranslationEntity $translationEntity)
    {
        $page = $route->getPage();

        $presenter = ucfirst($page->getPresenter()) == PageRoute::DEFAULT_PRESENTER
            ? ''
            : "/{$page->getPresenter()}";

        $action = $page->getAction() == PageRoute::DEFAULT_ACTION
            ? ''
            : "-{$page->getAction()}";

        $locale = $translationEntity->getLocale() == $route->getDefaultLocale()
            ? ''
            : "-{$translationEntity->getLocale()}";

        $newUrl = "{$page->getModule()}/$package{$presenter}{$action}{$locale}";
        return $newUrl;
    }

}