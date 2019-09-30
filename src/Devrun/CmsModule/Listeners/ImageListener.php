<?php
/**
 * This file is part of souteze.
 * Copyright (c) 2019
 *
 * @file    ImageListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Listeners;

use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\Storage\ImageStorage;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Kdyby\Events\Subscriber;


class ImageListener implements Subscriber
{

    /** @var ImageStorage  */
    private $imageStorage;


    /**
     * MediaDataListener constructor.
     *
     * @param ImageStorage $imageStorage
     */
    public function __construct(ImageStorage $imageStorage)
    {
        $this->imageStorage = $imageStorage;
    }


    /**
     * after remove entity, remove medium too
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        /** @var ImagesEntity $imageEntity */
        $imageEntity = $eventArgs->getEntity();

        if ($imageEntity instanceof ImagesEntity) {
            if ($identifier = $imageEntity->getIdentifier()) {
                $this->imageStorage->delete($identifier);
            }
        }
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postRemove,
        ];
    }
}