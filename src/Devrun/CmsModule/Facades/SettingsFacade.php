<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    SettingsFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Entities\SettingsEntity;
use Devrun\CmsModule\Repositories\SettingsRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\SmartObject;

class SettingsFacade
{

    use SmartObject;

    /** @var SettingsRepository */
    private $repository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * SettingsFacade constructor.
     *
     * @param SettingsRepository $repository
     */
    public function __construct(SettingsRepository $repository)
    {
        $this->repository = $repository;
        $this->entityManager = $repository->getEntityManager();
    }


    public function save($key, $value)
    {
        if (!$entity = $this->repository->find($key)) {
            $entity = new SettingsEntity($key, $value);
        } else {
            $entity->$key = $value;
        }

//        dump($entity);
//        die();


        $this->entityManager->persist($entity)->flush();
    }

    /**
     * @return SettingsRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }




}