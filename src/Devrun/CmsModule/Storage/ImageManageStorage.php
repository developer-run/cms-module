<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ImageStorage.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Storage;

use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\NotFoundResourceException;
use Devrun\CmsModule\Presenters\TCmsPresenter;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\Storage\ImageStorage;
use Devrun\Utils\Image;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class ImageManageStorage
 *
 * @package Devrun\CmsModule\Storage
 * @method onCreateImage(ImagesEntity $entity , $identifier)
 */
class ImageManageStorage implements Subscriber
{
    use SmartObject;


    /** @var ImageStorage */
    private $imageStorage;

    /** @var ImageRepository */
    private $imageRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var Request */
    private $request;

    /** @var RouteEntity */
    private $routeEntity;

    /** @var PageEntity */
    private $pageEntity;

    /** @var PackageEntity */
    private $packageEntity;



    /** @var Translator */
    private $translator;

    /** @var ImagesEntity[]  array load images of page */
    private $imageEntities = [];

    /** @var ImagesEntity[]  array used ImagesEntity on page */
    private $useInPageImages = [];

    /** @var callable */
    public $callCreateImage;

    /** @var callable[] */
    public $onCreateImage = [];

    /** @var bool flush directly or onShutdown @see FlushListener */
    private $autoFlush = false;

    /**
     * ImageStorage constructor.
     *
     * @param bool            $autoFlush
     * @param ImageStorage    $imageStorage
     * @param ImageRepository $imageRepository
     * @param EntityManager $entityManager
     * @param Translator      $translator
     */
    public function __construct(bool $autoFlush, ImageStorage $imageStorage, ImageRepository $imageRepository, EntityManager $entityManager, Translator $translator)
    {
        $this->autoFlush       = $autoFlush;
        $this->imageStorage    = $imageStorage;
        $this->imageRepository = $imageRepository;
        $this->entityManager   = $entityManager;
        $this->translator      = $translator;
    }




    /**
     * presenter startup set Request
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }


    /**
     * @return ImageStorage
     */
    public function getImageStorage(): ImageStorage
    {
        return $this->imageStorage;
    }


    /**
     * @param $args
     * @return array|\Contributte\ImageStorage\Image
     * @throws \Contributte\ImageStorage\Exception\ImageResizeException
     * @throws \Contributte\ImageStorage\Exception\ImageStorageException
     * @throws \Nette\Utils\ImageException
     * @throws \Nette\Utils\UnknownImageFileException
     */
    public function fromIdentifier($args)
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        /**
         * Define image identifier
         */
        $identifier = $args[0];

        if (isset($this->imageEntities[$identifier])) {
            $entity = $this->imageEntities[$identifier];

        } else {

            try {
                /*
                 * set where find the image
                 */
                $findBy = ['identify.referenceIdentifier' => $identifier];

                if ($args['page'] ?? false) {
                    $findBy['page'] = $this->getPageEntity();

                } elseif ($args['package'] ?? false) {
                    $findBy['package'] = $this->getPackageEntity();

                } elseif ($args['route'] ?? true) {
                    $findBy['route'] = $this->getRouteEntity();
                }

                /** @var ImagesEntity $entity */
                if (!$entity = $this->imageRepository->findOneBy($findBy)) {
                    if (!$identifyEntity = $this->imageRepository->getEntityManager()->getRepository(ImageIdentifyEntity::class)->findOneBy(['referenceIdentifier' => $identifier])) {
                        $identifyEntity = new ImageIdentifyEntity($identifier);
                    }

                    $entity = new ImagesEntity($this->translator, $identifyEntity);

                    if ($findBy['page'] ?? false) $entity->setPage($this->getPageEntity());
                    if ($findBy['route'] ?? false) $entity->setRoute($this->getRouteEntity());
                    if ($findBy['package'] ?? false) $entity->setPackage($this->getPackageEntity());
                }

            } catch (\Devrun\CmsModule\NotFoundResourceException $exception) {
                $font = "resources/cmsModule/fonts/OpenSansEasy/OpenSans-Regular.ttf";
                $image = Image::createImageText($font, 24, 320, 240, wordwrap($exception->getMessage(), 16));

                $dir = "images/temp";
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // change identifier to
                $identifier = "$dir/empty.png";
                $image->save($identifier);
                $data_dir = "";
                $data_path = "";

                return new \Contributte\ImageStorage\Image(false, $data_dir, $data_path, $identifier);
            }


            if (is_callable($this->callCreateImage)) {
                $entity = call_user_func_array($this->callCreateImage, [$entity, $identifier]);
                $this->onCreateImage($entity , $identifier);

                /*
                 * experimental off flush
                 * @see FlushListener
                 */
                if (!$this->autoFlush) {
                    try {
                        $this->imageRepository->getEntityManager()->flush();

                    } catch (UniqueConstraintViolationException $e) {
                        Debugger::log($e, "duplicateIdentifier");

                    } catch (\Exception $e) {
                        Debugger::log($e, ILogger::EXCEPTION);
                    }
                }
            }
            $this->imageEntities[$identifier] = $entity;
        }

        $args[0] = $entity->getIdentifier();
        unset($args['page'], $args['package'], $args['route']);

        $this->useInPageImages[$identifier] = $entity;
        return $this->imageStorage->fromIdentifier($args);
    }





    /**
     * @return \Devrun\CmsModule\Entities\RouteEntity
     */
    private function getRouteEntity(): RouteEntity
    {
        if (!$this->routeEntity) {
            if ($route = $this->request->getParameter('route')) {
                $this->routeEntity = $this->entityManager->getRepository(RouteEntity::class)->find($route);
            }
        }

        if (!$this->routeEntity) {
            throw new NotFoundResourceException("Unknown require route for image");
        }
        return $this->routeEntity;
    }

    /**
     * @return PageEntity
     */
    protected function getPageEntity(): PageEntity
    {
        if (!$this->pageEntity) {
            if ($page = $this->request->getParameter('page')) {
                $this->pageEntity = $this->entityManager->getRepository(PageEntity::class)->find($page);
            }
        }

        if (!$this->pageEntity) {
            throw new NotFoundResourceException("Unknown require page for image");
        }
        return $this->pageEntity;
    }

    /**
     * @return PackageEntity
     */
    protected function getPackageEntity(): PackageEntity
    {
        if (!$this->packageEntity) {
            if ($package = $this->request->getParameter('package')) {
                $this->packageEntity = $this->entityManager->getRepository(PackageEntity::class)->find($package);
            }
        }

        if (!$this->packageEntity) {
            throw new NotFoundResourceException("Unknown require package for image");
        }
        return $this->packageEntity;
    }
















    /**
     * @param ImagesEntity[] $imageEntities
     */
    public function setImageEntities(array $imageEntities)
    {
        $this->imageEntities = $imageEntities;
    }

    /**
     * @return ImagesEntity[]
     */
    public function getImageEntities(): array
    {
        return $this->imageEntities;
    }

    /**
     * @return ImagesEntity[]
     */
    public function getUseInPageImages(): array
    {
        return $this->useInPageImages;
    }


    /**
     * @param Presenter|TCmsPresenter $presenter
     */
    public function onStartup(Presenter $presenter)
    {
        if (isset(class_uses_recursive($presenter)[TCmsPresenter::class])) {

            try {
                $this->pageEntity    = $presenter->getPageEntity();
                $this->routeEntity   = $presenter->getRouteEntity();
                $this->packageEntity = $presenter->getPackageEntity();

            } catch (\Devrun\CmsModule\NotFoundResourceException $exception) {

            }

        } else {
//            throw new InvalidStateException("add TCmsPresenter trait to presenter please");
        }

        $this->request = $presenter->getRequest();
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'Nette\Application\UI\Presenter::onStartup'
        ];

    }
}