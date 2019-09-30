<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    DomainFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Entities\DomainEntity;
use Devrun\CmsModule\Facades\Domain\Validator;
use Devrun\CmsModule\Repositories\DomainRepository;
use Kdyby\Monolog\Logger;
use Nette\SmartObject;

/**
 * Class DomainFacade
 *
 * @package Devrun\CmsModule\Facades
 * @method onChangeDomain(DomainEntity $package)
 */
class DomainFacade
{

    use SmartObject;

    /** @var Validator */
    private $validator;

    /** @var DomainRepository */
    private $repository;

    /** @var array event on change package, etc edit domain ... */
    public $onChangeDomain = [];


    /**
     * DomainFacade constructor.
     *
     * @param string           $filenameDomain
     * @param array            $domainIPs
     * @param Logger           $logger
     * @param DomainRepository $domainRepository
     */
    public function __construct(string $filenameDomain, array $domainIPs, Logger $logger, DomainRepository $domainRepository)
    {
        $this->validator  = new Validator($filenameDomain, $domainIPs, $logger);
        $this->repository = $domainRepository;
    }


    /**
     * @return Validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * @return DomainRepository
     */
    public function getRepository(): DomainRepository
    {
        return $this->repository;
    }




}