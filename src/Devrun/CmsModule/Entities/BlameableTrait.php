<?php
/**
 * This file is part of doctrine-module
 * Copyright (c) 2016
 *
 * @file    BlameableTrait.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun;

trait BlameableTrait
{

    /**
     * @var UserEntity
     * Will be mapped to either string or user entity
     * by BlameableSubscriber
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity")
     */
    protected $createdBy;

    /**
     * @var UserEntity
     * Will be mapped to either string or user entity
     * by BlameableSubscriber
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity")
     */
    protected $updatedBy;

    /**
     * @var UserEntity
     * Will be mapped to either string or user entity
     * by BlameableSubscriber
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity")
     */
    protected $deletedBy;


    /**
     * @return UserEntity
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return UserEntity
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @return UserEntity
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param UserEntity $createdBy
     *
     * @return $this
     */
    public function setCreatedBy(UserEntity $createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @param UserEntity $deletedBy
     *
     * @return $this
     */
    public function setDeletedBy(UserEntity $deletedBy = NULL)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    /**
     * @param UserEntity $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy(UserEntity $updatedBy)
    {
        if ($updatedBy) {
            if (($meUpdatedById = $this->updatedBy ? $this->updatedBy->getId() : null) != $updatedBy->getId()) {
                $this->updatedBy = $updatedBy;
            }
        }

        return $this;
    }

    /**
     * Utility method, that can be replaced with `::class` since php 5.5
     *
     * @return string
     */
    public static function getBlameableTraitName()
    {
        return get_called_class();
    }


}