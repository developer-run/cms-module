<?php
/**
 * This file is part of devrun-advent_calendar.
 * Copyright (c) 2018
 *
 * @file    ImagePresenterListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Listeners;

use Devrun\Application\UI\Presenter\BasePresenter;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\CmsModule\Storage\ImageManageStorage;
use Kdyby\Events\Subscriber;
use Nette;

class ImagePresenterListener implements Subscriber
{

    /** @var RouteRepository */
    private $routeRepository;

    /** @var ImageRepository */
    private $imageRepository;

    /** @var ImageManageStorage */
    private $imageManageStorage;


    /**
     * ImagePresenterListener constructor.
     *
     * @param RouteRepository    $routeRepository
     * @param ImageRepository    $imageRepository
     * @param ImageManageStorage $imageManageStorage
     */
    public function __construct(RouteRepository $routeRepository, ImageRepository $imageRepository, ImageManageStorage $imageManageStorage)
    {
        $this->routeRepository     = $routeRepository;
        $this->imageRepository     = $imageRepository;
        $this->imageManageStorage  = $imageManageStorage;
    }


    /**
     * find route for presenter request
     *
     * @param Nette\Application\UI\Presenter $presenter
     */
    public function onBeforeRender(Nette\Application\UI\Presenter $presenter)
    {
        $sortIdentifierImages = [];
        $pageId       = $presenter->getParameter('page');
        $packageId    = $presenter->getParameter('package');

        if (!$route = $this->routeRepository->getRouteFromApplicationRequest($presenter->getRequest())) {
            $route = $this->routeRepository->findRouteFromApplicationPresenter($presenter);
        }

        if ($route) {
            $query = $this->imageRepository->createQueryBuilder('e')
                ->addSelect('t')
                ->addSelect('id')
                ->join('e.translations', 't')
                ->join('e.identify', 'id')
                ->where('e.route = :route')->setParameter('route', $route);

            if ($pageId) {
                $query->orWhere('e.page = :page')->setParameter('page', $pageId);
            }
            if ($packageId) {
                $query->orWhere('e.package = :package')->setParameter('package', $packageId);
            }

            /** @var ImagesEntity[] $images */
            $images = $query
                ->getQuery()
                ->getResult();

            foreach ($images as $image) {
                if ($image->getReferenceIdentifier()) $sortIdentifierImages[$image->getReferenceIdentifier()] = $image;
            }
        }

        $this->imageManageStorage->setImageEntities($sortIdentifierImages);
    }





    function getSubscribedEvents()
    {
        return [
            BasePresenter::BEFORE_RENDER_EVENT,
        ];
    }
}