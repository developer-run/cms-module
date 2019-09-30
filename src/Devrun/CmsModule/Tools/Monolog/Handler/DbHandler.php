<?php
/**
 * This file is part of DevRun
 * Copyright (c) 2017
 *
 * @file    DbHandler.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Tools\Monolog\Handler;

use Devrun\CmsModule\Entities\LogEntity;
use Devrun\Security\LoggedUser;
use Kdyby\Doctrine\EntityManager;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class DbHandler extends AbstractProcessingHandler
{

    /** @var EntityManager */
    private $entityManager;

    /** @var LoggedUser */
    private $loggedUser;

    /** @var bool */
    private $autoFlush = false;


    /**
     * DbHandler constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, LoggedUser $loggedUser, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->entityManager = $entityManager;
        $this->loggedUser    = $loggedUser;
    }


    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @example $this->logger->info("some", ['target' => $userEntity, 'targetID' => 125, 'flush' => false]);
     *
     * @return void
     */
    protected function write(array $record)
    {
        $context       = $record['context'];
        // $context['at'] = self::getSource();

        $record['context'] = array_merge($record['context'], $context);

        $targetID      = null;
        $realClassName = null;

        $action = isset($record['context']['action'])
            ? $record['context']['action']
            : 'other';

        if (isset($record['context']['target'])) {

            try {
                $targetEntity  = $record['context']['target'];
                unset($record['context']['target']);

                $metadata = $this->entityManager->getClassMetadata(get_class($targetEntity));
                $realClassName = $metadata->getName();

                if (count($identifiers = $metadata->getIdentifierValues($targetEntity)) == 1) {
                    $targetID = $identifiers[$metadata->getSingleIdentifierColumnName()];
                }

            } catch (\Doctrine\ORM\Mapping\MappingException $e) {
                // not Doctrine entity !
            }
        }

        if (isset($record['context']['targetID'])) {
            $targetID = $record['context']['targetID'];
        }

        $userEntity = $this->loggedUser->getUserEntity();
        $role = $userEntity ? ucfirst($userEntity->getRole()) : 'guest';

        $logEntity = new LogEntity($userEntity, $realClassName, $targetID, $action);

        $autoFlush = isset($record['context']['flush'])
            ? (bool)$record['context']['flush']
            : $this->autoFlush;

        unset($record['context']['flush']);

        $logEntity
            ->setRole($role)
            ->setMessage($record['message'])
            ->setLevel($record['level'])
            ->setLevelName($record['level_name'])
            ->setExtra($record['extra'])
            ->setContext($record['context']);

        if (isset($record['context']['type'])) {
            $logEntity->setType($record['context']['type']);
        }

        if (!$this->entityManager->isOpen()) {
//            $this->entityManager = $this->entityManager->create(
//                $this->entityManager->getConnection(),
//                $this->entityManager->getConfiguration()
//            );
        }

        if ($this->entityManager->isOpen()) {
            $this->entityManager->persist($logEntity);
            if ($autoFlush) {
                $this->entityManager->flush($logEntity);
            }
        }


    }


    /**
     * @internal
     * @author David Grudl
     * @see    https://github.com/nette/tracy/blob/922630e689578f6daae185dba251cded831d9162/src/Tracy/Helpers.php#L146
     */
    protected static function getSource()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
                . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '')
                . $_SERVER['REQUEST_URI'];

        } else {
            return empty($_SERVER['argv']) ? 'CLI' : 'CLI: ' . implode(' ', $_SERVER['argv']);
        }
    }


}