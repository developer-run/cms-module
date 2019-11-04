<?php

namespace Devrun\CmsModule\Security;

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

    /** @var UserFacade */
    private $userFacade;


    /**
     * Authenticator constructor.
     *
     * @param UserFacade $userFacade
     */
    function __construct(UserFacade $userFacade)
    {
        parent::__construct();
        $this->userFacade = $userFacade;
    }


    /**
     * Performs an authentication.
     *
     * @param array $credentials
     *
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function authenticate(array $credentials)
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

        /** @var $row array */
        $row = $this->userFacade->findByLogin($username);

        if (!$row) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::IDENTITY_NOT_FOUND);

        } elseif ($username !== $row[self::COLUMN_NAME]) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::INVALID_CREDENTIAL);

        } elseif (md5($username . $password) !== $row[self::COLUMN_PASSWORD_HASH]) {
            throw new Nette\Security\AuthenticationException('Neplatné přihlašovací údaje', self::INVALID_CREDENTIAL);
        }

        $arr = $row;
        unset($arr[self::COLUMN_PASSWORD_HASH]);
        unset($arr[self::COLUMN_NEW_PASSWORD_HASH]);
        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }


}