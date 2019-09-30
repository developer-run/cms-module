<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ImageStorage.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Storage;

use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\InvalidStateException;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\Storage\ImageStorage;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;
use Nette\Utils\Image;
use Nette\Utils\Validators;
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

    /** @var RouteRepository */
    private $routeRepository;

    /** @var Request */
    private $request;

    /** @var RouteEntity */
    private $applicationRoute;

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
     * @param RouteRepository $routeRepository
     * @param Translator      $translator
     */
    public function __construct(bool $autoFlush, ImageStorage $imageStorage, ImageRepository $imageRepository, RouteRepository $routeRepository, Translator $translator)
    {
        $this->autoFlush       = $autoFlush;
        $this->imageStorage    = $imageStorage;
        $this->imageRepository = $imageRepository;
        $this->routeRepository = $routeRepository;
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
            if (!$route  = $this->getApplicationRoute()) {
                throw new InvalidStateException(__METHOD__);
            }

            if (!$entity = $this->imageRepository->findOneBy(['identify.referenceIdentifier' => $identifier, 'route' => $route])) {
                if (!$identifyEntity = $this->imageRepository->getEntityManager()->getRepository(ImageIdentifyEntity::class)->findOneBy(['referenceIdentifier' => $identifier])) {
                    $identifyEntity = new ImageIdentifyEntity($identifier);
                }

                $entity = new ImagesEntity($this->translator, $identifyEntity);

                $entity->setRoute($route);
                if (is_callable($this->callCreateImage)) {

                    $entity = call_user_func_array($this->callCreateImage, [$entity, $identifier]);
                    $this->onCreateImage($entity , $identifier);

                    /*
                     * experimental off flush
                     * @see FlushListener
                     */
                    if (!$this->autoFlush) {
                        $this->imageRepository->getEntityManager()->flush();
                    }
                }
            }
            $this->imageEntities[$identifier] = $entity;
        }

        $this->useInPageImages[$identifier] = $entity;
        $args[0] = $entity->getIdentifier();

        return $this->imageStorage->fromIdentifier($args);
    }





    /**
     * @return \Devrun\CmsModule\Entities\RouteEntity|null
     */
    private function getApplicationRoute()
    {
        if (null === $this->applicationRoute) {
            if (!$this->request) {
                return null;
            }

            if (!$this->applicationRoute = $this->routeRepository->getRouteFromApplicationRequest($this->request)) {
                throw new InvalidStateException("old method without `PageRoute` route");
            }
        }

        return $this->applicationRoute;
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




    public function onStartup(Presenter $presenter)
    {
        $request=$presenter->getRequest();
        if (!$request->getParameter('package')) {
//            throw new InvalidStateException("request {$request->presenterName} has not a package set!");
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