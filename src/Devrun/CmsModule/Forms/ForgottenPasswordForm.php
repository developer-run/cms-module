<?php
/**
 * This file is part of karl.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ForgottenPasswordForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Devrun\CmsModule\Entities\UserEntity;
use Kdyby\Monolog\Logger;
use Kdyby\Translation\Phrase;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Form;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

interface IForgottenPasswordFormFactory
{
    /** @return ForgottenPasswordForm */
    function create();
}

/**
 * Class ForgottenPasswordForm
 *
 * @package CmsModule\Forms
 */
class ForgottenPasswordForm extends DevrunForm
{

    /** @var IMailer @inject */
    public $mailer;

    /** @var Logger @inject */
    public $logger;


    /** @var bool sending email? (DI) */
    private $emailSending = true;

    /** @var string sending from email? (DI) */
    private $emailFrom;

    /** @var UserEntity */
    private $existUserEntity = null;


    /**
     * @return ForgottenPasswordForm
     */
    public function create()
    {
        $this->addText('email', 'email')
            ->setHtmlAttribute('placeholder', "placeholder.email")
            ->addRule(Form::FILLED, 'ruleEMail')
            ->addRule(Form::EMAIL, 'valid_email');

        $password = $this->addPassword('password', 'password');
        $password->setHtmlAttribute('placeholder', "placeholder.password")
            ->addRule(Form::FILLED, 'rulePassword')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addPassword('password2', 'confirm_password')
            ->setHtmlAttribute('placeholder', "placeholder.confirm_password")
            ->addRule(Form::FILLED, 'ruleConfirmPassword')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255)
            ->addConditionOn($password, Form::FILLED)
            ->addRule(Form::EQUAL, 'password_dont_match', $password);

        $this->addSubmit('send', 'send')->setHtmlAttribute('class', 'btn btn-md btn-inverse btn-block');
        $this->onSuccess[] = array($this, 'formSuccess');

        $this->onValidate[] = [$this, 'validateEmail'];
        return $this;
    }


    public function validateEmail(ForgottenPasswordForm $form, $values) {

        $mail = $values->email;

        if (!$existUserEntity = $this->getExistUserEntity($mail)) {
            /** @var Translator $translator */
            $translator = $this->getTranslator();
            $this->addError($translator->translate('user_form_not_exist', ['email' => $mail]), false );
            return false;
        }

        return true;
    }


    public function formSuccess(ForgottenPasswordForm $form, $values)
    {

        /** @var UserEntity $entity */
        $entity = $form->getEntity();
        $newUser = $entity->getId() == null;

        if ($this->emailSending && $newUser) {
            $this->sendMail($values->email);
        }

    }



    /**
     * @return UserEntity
     */
    private function getExistUserEntity($mail)
    {
        if (null === $this->existUserEntity) {
            $em = $this->getEntityMapper()->getEntityManager();
            $this->existUserEntity = $em->getRepository(UserEntity::class)->findOneBy(['email' => $mail]);
        }

        return $this->existUserEntity;
    }


    /**
     * send email
     *
     * @param $mail
     */
    private function sendMail($mail)
    {
        /** @var AdminPresenter $presenter */
        $presenter  = $this->getPresenter();
        $translator = $this->getTranslator();
        $title      = $translator->translate('userPage.management');
        $userEntity = $this->getExistUserEntity($mail);

        $latte  = new \Latte\Engine;
        $params = [
            'url'      => $presenter->link("//:Cms:Login:forgottenPassword"),
            'link' => $presenter->link("//:Cms:Login:changePassword", ['id' => $userEntity->getId(), 'code' => $userEntity->getNewPassword()]),
        ];

        $message = new Message();
        $message->setFrom($this->emailFrom)
            ->addTo($mail)
            ->setHtmlBody($latte->renderToString(__DIR__ . "/template/forgottenEmail.latte", $params));

        $this->mailer->send($message);
        $this->logger->info("{$userEntity->getRole()} {$userEntity->getUsername()} sendMail", ['type' => LogEntity::ACTION_ACCOUNT, 'target' => $userEntity, 'action' => 'email send ok']);

        $message = $translator->translate('user_has_been_send_email');
        $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_INFO);
    }


    /**
     * @param bool $emailSending
     *
     * @return $this
     */
    public function setEmailSending($emailSending)
    {
        $this->emailSending = (bool)$emailSending;
        return $this;
    }

    /**
     * @param string $emailFrom
     *
     * @return $this
     */
    public function setEmailFrom($emailFrom)
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }


}