<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    RegistrationForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Devrun\CmsModule\Entities\UserEntity;
use Flame\Application\UI\Form;
use Kdyby\Translation\Phrase;
use Kdyby\Translation\Translator;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

interface IRegistrationFormFactory
{
    /** @return RegistrationForm */
    function create();
}

class RegistrationForm extends DevrunForm
{


    /** @var IMailer @inject */
    public $mailer;

    /** @var bool sending email? (DI) */
    private $emailSending = true;

    /** @var string sending from email? (DI) */
    private $emailFrom;



    /** @return RegistrationForm */
    function create()
    {

        $this->addText('firstName', 'first_name')
            ->setAttribute('placeholder', "placeholder.first_name")
            ->addRule(Form::FILLED, 'ruleFirstName')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('lastName', 'last_name')
            ->setAttribute('placeholder', "placeholder.last_name")
            ->addRule(Form::FILLED, 'ruleLastName')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('email', 'email')
            ->setAttribute('placeholder', "placeholder.email")
            ->addRule(Form::FILLED, 'ruleEMail')
            ->addRule(Form::EMAIL, 'valid_email');

        $password = $this->addPassword('password', 'password');
        $password->setAttribute('placeholder', "placeholder.password")
            ->addRule(Form::FILLED, 'rulePassword')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addPassword('password2', 'confirm_password')
            ->setAttribute('placeholder', "placeholder.confirm_password")
            ->addRule(Form::FILLED, 'ruleConfirmPassword')
            ->addConditionOn($password, Form::FILLED)
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255)
            ->addRule(Form::EQUAL, 'password_dont_match', $password);

        $this->addCheckbox('privacy', 'privacy')->getControl()->class[] = 'icheck';
        $this->addSubmit('send', 'registration')->setAttribute('class', 'btn btn-primary btn-md pull-right');
        $this->onSuccess[] = array($this, 'formSuccess');

        $this->onValidate[] = [$this, 'validateEmail'];

        return $this;
    }


    public function validateEmail(RegistrationForm $form, $values) {

        $mail = $values->email;
        $em = $this->getEntityMapper()->getEntityManager();

        if ($existUserEntity = $em->getRepository(UserEntity::class)->findOneBy(['email' => $mail])) {
            /** @var Translator $translator */
            $translator = $this->getTranslator();
            $this->addError($translator->translate('user_form_duplicate_error', ['email' => $mail]), false );
            return false;
        }

        return true;
    }


    public function formSuccess(RegistrationForm $form, $values)
    {

        /** @var UserEntity $entity */
        $entity = $form->getEntity();
        $newUser = $entity->getId() == null;

        if ($this->emailSending && $newUser) {
            $this->sendMail($values);
        }

    }


    /**
     * send email
     *
     * @param $values
     */
    private function sendMail($values)
    {
        /** @var AdminPresenter $presenter */
        $presenter  = $this->getPresenter();
        $translator = $this->getTranslator();
        $title      = $translator->translate('userPage.management');

        $latte  = new \Latte\Engine;
        $params = [
            'url'      => $presenter->link("//:Cms:Default:"),
            'username' => $values->email,
            'password' => $values->password,
        ];

        $message = new Message();
        $message->setFrom($this->emailFrom)
            ->addTo($values->email)
            ->setHtmlBody($latte->renderToString(__DIR__ . "/template/email.latte", $params));

//        echo $message->htmlBody;
//        dump($message);
//        dump($message);
//        die();


        $this->mailer->send($message);

        $message = $translator->translate('user_has_been_send_email');
        $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, $title, FlashMessageControl::TOAST_SUCCESS);
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
    public function setEmailFrom(string $emailFrom)
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }




}