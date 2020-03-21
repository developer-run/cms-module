<?php

namespace Devrun\CmsModule\Security;

use Devrun\CmsModule\Entities\UserEntity;
use Devrun\CmsModule\Repositories\UserRepository;
use Devrun\Facades\UserFacade;
use Nette;

/**
 * Class Authenticator
 * @package Devrun\CmsModule\Security
 */
class Authenticator extends \Devrun\Security\Authenticator
{

    const
        COLUMN_ID = 'id',
        COLUMN_NAME = 'username',
        COLUMN_ROLE = 'role',
        COLUMN_PASSWORD_HASH = 'password',
        COLUMN_NEW_PASSWORD_HASH = 'newPassword',
        COLUMN_MEMBER = 'member',
        COLUMN_MEMBER_ID = 'memberId';

    /** @var UserRepository */
    private $userRepository;


    /**
     * Authenticator constructor.
     *
     * @param $adminLogin
     * @param $adminPassword
     * @param UserRepository $userRepository
     */
    public function __construct($adminLogin, $adminPassword, UserRepository $userRepository)
    {
        parent::__construct($adminLogin, $adminPassword);
        $this->userRepository = $userRepository;
    }


    /**
     * Performs an authentication.
     *
     * @param array $credentials
     *
     * @return UserEntity
     * @throws Nette\Security\AuthenticationException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function authenticate(array $credentials): Nette\Security\IIdentity
    {
        if (count($credentials) == 2) {
            list($username, $password) = $credentials;

        } elseif (count($credentials) == 1) {
            list($username) = $credentials;
            $password = null;

        } else {
            $username = null;
            $password = null;
        }

        /** @var $row UserEntity */
        $row = $this->userRepository->findOneBy([self::COLUMN_NAME => $username]);

        if (!$row) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::IDENTITY_NOT_FOUND);

        } elseif ($username !== $row->{self::COLUMN_NAME}) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::INVALID_CREDENTIAL);

        } elseif (md5($username . $password) !== $row->{self::COLUMN_PASSWORD_HASH}) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::INVALID_CREDENTIAL);
        }

        return $row;
//        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }


}