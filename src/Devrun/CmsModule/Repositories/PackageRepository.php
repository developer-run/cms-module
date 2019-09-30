<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PackageRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Repositories\Queries\PackageQuery;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\ResultSet;
use Nette\Security\User;
use Nette\Utils\FileSystem;

class PackageRepository extends EntityRepository
{

    /** @var User @inject */
    public $user;

    private $wwwDir;


    /**
     * @deprecated use PageCaptureRepository instead
     *
     * @param PackageEntity $packageEntity
     *
     * @return string
     */
    public function getPreview(PackageEntity $packageEntity)
    {
        $path = "images/" . $packageEntity->getModule() . "/theme-$packageEntity" . DIRECTORY_SEPARATOR . "preview.jpg";

        if (!file_exists($path)) {
            if (file_exists($origPath = "images/" . $packageEntity->getModule() . "/theme-default" . DIRECTORY_SEPARATOR . "preview.jpg")) {
                FileSystem::copy($origPath, $path);
            }
        }

        return $path;
    }


    public function getAllowedPackages($module)
    {
        $query = (new PackageQuery())->byModule($module);

        if (!$this->user->isAllowed('Cms:Page', 'editAllPackages')) {
            $query->byUser($this->user);
        }

        return $query->fetch($this);
    }



    public function getPairs(ResultSet $results, $key, $value)
    {
        $rows = [];
        foreach ($results as $result) {
            $rows[$result->$key] = $result->$value;
        }

        return $rows;

    }


    /**
     * Returns associated array  module packages count
     *
     * @return array
     */
    public function getPackageInstancesCount()
    {
        $results = $this->createQueryBuilder('e')
            ->select("count(e)")
            ->addSelect("e")
            ->groupBy('e.module')
            ->getQuery()
            ->getResult();

        $pairResult = [];
        foreach ($results as $result) {
            $pairResult[$result[0]->module] = intval($result[1]);
        }

        return $pairResult;
    }


    /**
     *
     *
     * @param PackageEntity      $newPackage
     * @param PackageEntity|null $oldPackage
     *
     * @return array
     */
    public function getSourceRoutes(PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->addSelect('t')
            ->addSelect('page')
            ->from(RouteEntity::class, 'e')
            ->join('e.page', 'page')
            ->join('e.translations', 't')
            ->where('page.module = :module')->setParameter('module', $newPackage->getModule());

        if ($oldPackage) {
            $query->andWhere('e.package = :package')->setParameter('package', $oldPackage);

        } else {
            $query->andWhere('e.package IS NULL');
        };

        $oldRouteEntities = $query
            ->getQuery()
            ->getResult();

        return $oldRouteEntities;
    }


}