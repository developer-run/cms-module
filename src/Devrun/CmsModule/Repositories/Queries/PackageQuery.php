<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PackageQuery.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories\Queries;

use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\InvalidArgumentException;
use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Nette\Security\User;

class PackageQuery extends QueryObject
{

    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];





    public function byUser($user)
    {
        if (!$user instanceof User) {
            throw new InvalidArgumentException();
        }

        $this->filter[] = function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('q.user = :user')->setParameter('user', $user->getId());
        };
        return $this;
    }


    public function byModule($module)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($module) {
            $qb->andWhere('q.module = :module')->setParameter('module', $module);
        };
        return $this;
    }






    /**
     * @param \Kdyby\Persistence\Queryable $repository
     *
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }


    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        return $this->createBasicDql($repository)->select('COUNT(q.id)');
    }



    private function createBasicDql(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('q')->from(PackageEntity::class, 'q')
            ->orderBy('q.position');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }


}