<?php
/**
 * This file is part of doctrine-module
 * Copyright (c) 2016
 *
 * @file    UserEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Nette\Security\IIdentity;
use Nette\Utils\DateTime;

/**
 * Class UserEntity
 *
 * @ORM\Cache(region="user_entity", usage="NONSTRICT_READ_WRITE")
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\UserRepository")
 * @ORM\Table(name="users",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="username_email_idx", columns={"username", "email"}),
 *      @ORM\UniqueConstraint(name="username_idx", columns={"username"})
 * },
 *  indexes={
 *      @ORM\Index(name="hash_idx", columns={"hash"}),
 *      @ORM\Index(name="newPassword_idx", columns={"new_password"}),
 *      @ORM\Index(name="first_last_name_idx", columns={"first_name", "last_name"}),
 *      @ORM\Index(name="active_idx", columns={"active"}),
 *      @ORM\Index(name="role_idx", columns={"role"}),
 *      @ORM\Index(name="nickname_idx", columns={"nickname"}),
 *      @ORM\Index(name="user_email_idx", columns={"email"}),
 *  })
 * })
 * @ORM\HasLifecycleCallbacks
 * @package Devrun\DoctrineModule\Entities
 */
class UserEntity implements IIdentity
{
    use MagicAccessors;
    use DateTimeTrait;
    use IdentifiedEntityTrait;

    const ROLE_GUEST = 'guest';
    const ROLE_MEMBER = 'member';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPERUSER = 'supervisor';
    const ROLE_DEVRUN = 'devrun';


    /**
     * @var PackageEntity[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="PackageEntity", inversedBy="users")
     * @ORM\JoinTable(name="packages_users")
     */
    protected $packages;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, unique=true, nullable=true)
     */
    protected $hash;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $lastName;

    /**
     * @var DateTime
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birthday;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $gender;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(type="string", length=13, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $nickname;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $password;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $newPassword;

    /**
     * @var string
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $street;

    /**
     * @var string
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $psc;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $active = false;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $activeDateTime;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $privacy = false;

    /**
     * @var string
     * @ORM\Column(type="text", length=65536, nullable=true)
     */
    protected $privacyPolicyLegalText;

    /**
     * @var string enum
     * @ORM\Column(type="text", length=65536, nullable=true)
     */
    protected $privacyPolicyConsentText;

    /**
     * @var string enum
     * @ORM\Column(type="string")
     */
    protected $role;

    /**
     * UserEntity constructor.
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
    }


    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     *
     * @return $this
     */
    public function setActive($active = true)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getActiveDateTime()
    {
        return $this->activeDateTime;
    }

    /**
     * @param \DateTime $activeDateTime
     * @return $this
     */
    public function setActiveDateTime(\DateTime $activeDateTime)
    {
        $this->activeDateTime = $activeDateTime;
        return $this;
    }

    /**
     * @param PackageEntity $packageEntity
     *
     * @return $this
     */
    public function addPackage(PackageEntity $packageEntity): UserEntity
    {
        if (!$this->packages->contains($packageEntity)) {
            $this->packages->add($packageEntity);
        }
        return $this;
    }


    /**
     * @return UserEntity
     */
    public function removePackages(): UserEntity
    {
        $this->packages->clear();
        return $this;
    }



    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $mail
     *
     * @return $this
     */
    public function setEmail($mail)
    {
        $this->email = $mail;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "{$this->firstName} {$this->lastName}";
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGenderMale()
    {
        return $this->gender == true;
    }


    /**
     * @return bool
     */
    public function isGenderFemale()
    {
        return $this->gender == false;
    }


    /**
     * @param bool $gender
     *
     * @return $this
     */
    public function setGender($gender)
    {
        $this->gender = (bool)$gender;
        return $this;
    }

    /**
     * @return bool
     */
    public function getGender()
    {
        return intval($this->gender);
    }

    /**
     * @return string
     */
    public function getPsc()
    {
        return $this->psc;
    }

    /**
     * @param string $psc
     *
     * @return UserEntity
     */
    public function setPsc(string $psc): UserEntity
    {
        $this->psc = $psc;
        return $this;
    }






    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password = null)
    {
        $this->password = md5($this->username . $password);
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }


    public function getFullName()
    {
        return "$this->firstName $this->lastName";
    }


    /**
     * @param string $newPassword
     *
     * @return $this
     */
    public function generateNewPassword($newPassword)
    {
        $this->newPassword = md5($this->username . $newPassword);
        return $this;
    }

    /**
     * @return $this
     */
    public function activateNewPassword()
    {
        if ($this->newPassword) {
            $this->password    = $this->newPassword;
            $this->newPassword = null;
        }
        return $this;
    }

    /**
     * @param string $newPassword
     *
     * @return $this
     */
    public function setNewPassword(string $newPassword)
    {
        $this->newPassword = md5($this->username . $newPassword);
        return $this;
    }


    public function setPasswordFromNewPassword()
    {
        if ($this->newPassword) {
            $this->password = $this->newPassword;
            $this->newPassword = null;
        }
    }

    /**
     * @return string
     */
    public function getNewPassword()
    {
        return $this->newPassword;
    }

    /**
     * @param string $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        if ($phone != $this->phone) $this->phone = $phone;
        return $this;
    }





    /**
     * @return string
     */
    public function getBirthDayToString()
    {
        return $this->birthDay ? $this->birthDay->format('Y-m-d') : NULL;
    }

    /**
     * @return DateTime
     */
    public function getBirthDay()
    {
        return $this->birthday;
    }

    /**
     * @param string $birthday
     *
     * @return $this
     */
    public function setBirthday($birthday)
    {
        if (is_string($birthday)) {
            $birthday = DateTime::from($birthday);
        }
        if ($birthday instanceof DateTime) {
            $birthday->setTime(0, 0, 0);
            if ($birthday != $this->birthday) $this->birthday = $birthday;
        }
        return $this;
    }

    public function setBirthdayFromParts($day, $month, $year)
    {
        $birthday = DateTime::fromParts($year, $month, $day);
        if ($birthday != $this->birthday) $this->birthday = $birthday;
        return $this;
    }






    function __toString()
    {
        return $this->nickname ? $this->nickname : '';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @inheritDoc
     */
    function getRoles(): array
    {
        return [$this->role];
    }


}