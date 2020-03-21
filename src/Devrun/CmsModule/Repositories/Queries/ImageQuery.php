<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ImageQuery.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories\Queries;

use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\InvalidArgumentException;
use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Nette\Security\User;

class ImageQuery extends QueryObject
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
            $qb->andWhere('q.createdBy = :user')->setParameter('user', $user->getId());
        };
        return $this;
    }

    /**
     * @param $route
     *
     * @return ImageQuery
     */
    public function byRoute($route)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($route) {
            $qb->andWhere('q.route = :route')->setParameter('route', $route);
        };
        return $this;
    }


    public function byPackage($package)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($package) {
            $qb->andWhere('r.package = :package')->setParameter('package', $package);
        };
        return $this;
    }


    public function withTranslations()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('t')
                ->join('q.translations', 't');
        };
        return $this;
    }

    public function withRoute()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('r')
                ->join('q.route', 'r');
        };
        return $this;
    }

    public function withIdentify()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('id')
                ->join('q.identify', 'id');
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
            ->select('q')->from(ImagesEntity::class, 'q');


        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }


}