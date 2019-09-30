<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    UserGroupEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\Doctrine\Entities\IdentifiedEntityTrait;
use Devrun\Doctrine\Entities\UserEntity;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class UserGroupEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="package_users",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="group_username_idx", columns={"user_id", "name"}),
 * },
 * )
 * @package Devrun\CmsModule\Entities
 */
class PackageUsersEntity
{

    use IdentifiedEntityTrait;
    use MagicAccessors;


    /**
     * @var UserEntity
     * @ORM\ManyToOne(targetEntity="Devrun\Doctrine\Entities\UserEntity", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $name;

    /**
     * UsersGroupEntity constructor.
     *
     * @param UserEntity $user
     * @param string     $name
     */
    public function __construct(UserEntity $user, string $name)
    {
        $this->user = $user;
        $this->name = $name;
    }

    /**
     * @return UserEntity
     */
    public function getUser(): UserEntity
    {
        return $this->user;
    }

    /**
     * @param UserEntity $user
     */
    public function setUser(UserEntity $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }





}