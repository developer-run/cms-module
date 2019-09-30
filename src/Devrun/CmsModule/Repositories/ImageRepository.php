<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImageRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Repositories\Queries\ImageQuery;
use Kdyby\Doctrine\EntityRepository;

class ImageRepository extends EntityRepository
{


    /**
     * @return array
     */
    public function getAssocImages($namespace = null)
    {
        if ($namespace === null) {
            $images = $this->createQueryBuilder('e')
                ->addSelect('t')
                ->join('e.translations', 't')
                ->getQuery()
                ->getResult();

        } else {
            $images = $this->createQueryBuilder('e')
                ->addSelect('t')
                ->leftJoin('e.translations', 't')
                ->where('e.namespace = :namespace')->setParameter('namespace', $namespace)
                ->getQuery()
                ->getResult();
        }


        $assocImages = [];
        foreach ($images as $image) {
            $assocImages[$image->namespace][] = $image;
        }

        return $assocImages;
    }


    public function getQuery()
    {
        return new ImageQuery();
    }


}