<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    LoginPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\UserEntity;
use Devrun\CmsModule\Forms\ForgottenPasswordForm;
use Devrun\CmsModule\Forms\IForgottenPasswordFormFactory;
use Devrun\CmsModule\Forms\ILoginFormFactory;
use Devrun\CmsModule\Forms\IRegistrationFormFactory;
use Devrun\CmsModule\Forms\LoginForm;
use Devrun\CmsModule\Forms\RegistrationForm;
use Devrun\CmsModule\Repositories\UserRepository;
use Nette\Security\AuthenticationException;

class LoginPresenter extends AdminPresenter
{

    /** @persistent */
    public $backlink = '';

    /** @var ILoginFormFactory @inject */
    public $loginForm;

    /** @var IRegistrationFormFactory @inject */
    public $registrationFormFactory;

    /** @var IForgottenPasswordFormFactory @inject */
    public $forgottenPasswordFormFactory;

    /** @var UserRepository @inject */
    public $userRepository;



    public function renderDefault()
    {
        $this->template->website = $this->websiteInfo;
    }


    public function actionChangePassword($id, $code)
    {
        /** @var UserEntity $userEntity */
        if (!$userEntity = $this->userRepository->find($id)) {
            $title = $this->translator->translate("messages.loginPage.forgottenUserTitle");
            $this->flashMessage($this->translator->translate("messages.loginPage.userNotFound"), FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_WARNING);
            $this->ajaxRedirect(":Cms:Login:");
        }

        if ($userEntity->getNewPassword() != $code) {
            $title = $this->translator->translate("messages.loginPage.forgottenUserTitle");
            $this->flashMessage($this->translator->translate("messages.loginPage.userCodeNotValid"), FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_WARNING);
            $this->ajaxRedirect(":Cms:Login:forgottenPassword");
        }

        $em = $this->userRepository->getEntityManager();
        $userEntity->setPasswordFromNewPassword();
        $em->persist($userEntity)->flush();

        $title = $this->translator->translate("messages.loginPage.forgottenUserTitle");
        $this->flashMessage($this->translator->translate("messages.loginPage.userCodeChanged"), FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_SUCCESS);
        $this->ajaxRedirect(":Cms:Login:");
    }








    protected function createComponentLoginForm()
    {
        $form = $this->loginForm->create();

        $form->create()
            ->bootstrap3Render()
            ->bindEntity(new UserEntity())
            ->onSuccess[] = function (LoginForm $form) {

            $this->flashMessage("uživatel `{$form->values->username}` přihlášen", 'success');
//            $this->restoreRequest($this->backlink);
            $this->ajaxRedirect(":Cms:{$this->administrationManager->defaultPresenter}:");
        };

        return $form;
    }


    /**
     * @param $name
     *
     * @return RegistrationForm
     */
    protected function createComponentRegistrationForm($name)
    {
        $form = $this->registrationFormFactory->create();
        $form->setTranslator($this->translator->domain("admin.forms.$name"));

        if (!$userEntity = $this->getUserEntity()) {
            $userEntity = new UserEntity();
        }

        $form->create()
            ->bootstrap3Render()
            ->bindEntity($userEntity)
            ->onSuccess[] = function (RegistrationForm $form, $values) use ($name) {

            /** @var UserEntity $entity */
            $entity = $form->getEntity();
            $entity
                ->setRole(UserEntity::ROLE_MEMBER)
                ->setActive(true)
                ->setUsername($values->email)
                ->setPassword($values->password);

            try {
                $em = $form->getEntityMapper()->getEntityManager();
                $em->persist($entity)->flush();

                $this->user->login($values->email, $values->password);
                $message = $this->translator->translate("admin.registrationPage.userAdded", null, ['username' => $entity->getUsername()]);
                $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, $this->translator->translate("admin.registrationPage.title"), FlashMessageControl::TOAST_SUCCESS);
                $this->ajaxRedirect(':Cms:Default:');

            } catch (AuthenticationException $exception) {
                $message = $this->translator->translate("admin.registrationPage.userInvalid", null, ['username' => $entity->getUsername()]);
                $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, $this->translator->translate("admin.registrationPage.title"), FlashMessageControl::TOAST_WARNING);
                $this->ajaxRedirect('this');
            }

        };

        return $form;
    }


    protected function createComponentForgottenPasswordForm($name)
    {
        $form = $this->forgottenPasswordFormFactory->create();
        $form->setTranslator($this->translator->domain("admin.forms.$name"));

        if (!$userEntity = $this->getUserEntity()) {
            $userEntity = new UserEntity();
        }

        $form->create()
            ->bootstrap3Render()
            ->bindEntity($userEntity)
            ->onSuccess[] = function (ForgottenPasswordForm $form, $values) {

            /** @var UserEntity $existUserEntity */
            if ($existUserEntity = $this->userRepository->findOneBy(['email' => $mail = $values->email])) {
                $existUserEntity->setNewPassword($values->password);

                $em = $form->getEntityMapper()->getEntityManager();
                $em->persist($existUserEntity)->flush();

                /*
                 * send control email
                 *
                 * email send from form yet
                 */

            }

            $title = $this->translator->translate("admin.registrationPage.title");
            $message = $this->translator->translate("admin.forgottenPasswordPage.userCodeSend", null, ['mail' => $mail]);
//            $this->flashMessage($this->translator->translate("admin.forgottenPasswordPage.userCodeSend", null, ['mail' => $mail]), FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_SUCCESS);

            $this->flashMessage($message, 'info');
            $this->ajaxRedirect('default');
        };

        return $form;
    }


    /**
     * DI setter
     *
     * @param array $websiteInfo
     */
    public function setWebsiteInfo($websiteInfo)
    {
        $this->websiteInfo = $websiteInfo;
    }




}