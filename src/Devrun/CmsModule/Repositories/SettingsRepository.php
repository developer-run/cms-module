<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    SettingsRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use Kdyby\Doctrine\EntityRepository;

class SettingsRepository extends EntityRepository
{


    public function getValue($id, $default = null)
    {
        $result = $this->createQueryBuilder('e')
            ->select('e.value')
            ->where('e.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['value'] : $default;
    }


}