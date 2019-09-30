<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    LogListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Listeners;

use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\LogEntity;
use Devrun\CmsModule\Forms\DevrunForm;
use Devrun\CmsModule\Repositories\LogRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Kdyby\Monolog\Logger;

class LogListener implements Subscriber
{

    /** @var EntityManager */
    private $entityManager;

    /** @var LogRepository */
    private $logRepository;

    /** @var Logger */
    private $logger;

    /**
     * LogListener constructor.
     *
     * @param EntityManager $entityManager
     * @param LogRepository $logRepository
     * @param Logger        $logger
     */
    public function __construct(EntityManager $entityManager, LogRepository $logRepository, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->logRepository = $logRepository;
        $this->logger        = $logger;
    }

    public function onCreateImage(ImagesEntity $imagesEntity, $identify)
    {
        $message = "inserted $identify";
        $this->logger->info("{$imagesEntity->getReferenceIdentifier()} has been $message", ['type' => LogEntity::ACTION_ACCOUNT, 'target' => $imagesEntity, 'action' => 'switch activation handle']);
    }







    private function persistFormSuccess($name, DevrunForm $form, $action)
    {

        $entity      = $form->getEntity();
        $persistType = $entity->getId() ? "updated" : "inserted";
        $formName    = $form->getFormName();

        $this->logger->info("$name [$formName] `{$entity->getName()}` has been $persistType", ['type' => LogEntity::ACTION_FORM, 'target' => $entity, 'action' => $action, 'name' => get_class($form)]);
    }


    /**
     * update log target key if insertion entity
     *
     * @param LifecycleEventArgs $lifecycleEventArgs
     */
    public function postPersist(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();
        $em     = $lifecycleEventArgs->getEntityManager();
        $uow    = $lifecycleEventArgs->getEntityManager()->getUnitOfWork();

        $className  = get_class($entity);
        $insertions = $uow->getScheduledEntityInsertions();

        foreach ($insertions as $id => $insertion) {

            if ($insertion instanceof LogEntity) {
                if ($insertion->getTargetKey() == null && $insertion->getTarget() == $className) {
                    $insertion->setTargetKey($entity->id);
                    $metaData = $em->getClassMetaData(get_class($insertion));
                    $uow->recomputeSingleEntityChangeSet($metaData, $insertion);
                }
            }

        }
    }


    function getSubscribedEvents()
    {
        return [
            'Devrun\CmsModule\Storage\ImageManageStorage::onCreateImage' => [array('onCreateImage', 20)],

            Events::postPersist,
        ];
    }
}