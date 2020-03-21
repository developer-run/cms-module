<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    LoginForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Nette\Application\UI\Form;
use Nette\Security\IUserStorage;

interface ILoginFormFactory
{
    /** @return LoginForm */
    function create();
}

/**
 * Class LoginForm
 *
 * @package CmsModule\Forms
 * @method onLoggedIn($form, $user)
 */
class LoginForm extends DevrunForm implements ILoginFormFactory
{

    protected $labelControlClass = 'div class="hidden"';

    protected $controlClass = 'div class=col-sm-12';


    public $onLoggedIn = [];

    /** @return LoginForm */
    function create()
    {
        /*
         * @todo vyřazeno kvůli testům, navázat pole na konfiguraci
         */
//        $this->addProtection('Vypršela platnost zabezpečovacího tokenu. Prosím, odešlete přihlašovací formulář znovu.');

        $this->addText('username')
            ->setAttribute('placeholder', "Přihlašovací jméno")
            //->setAttribute('type', "email")
            ->addRule(Form::FILLED, 'Zadejte přihlašovací jméno.')
            ->addRule(Form::MIN_LENGTH, 'Přihlašovací jméno musí mít minimálně 4 znaky.', 4)
            ->addRule(Form::MAX_LENGTH, 'Přihlašovací jméno může mít maximálně 32 znaků.', 32);

        $this->addPassword('password')
            ->setAttribute('placeholder', "Heslo")
            ->addRule(Form::FILLED, 'Zadejte heslo.');

        $this->addCheckbox('remember', 'Zapamatovat si')->getControl()->class[] = 'icheck text-left';
        $this->addSubmit('send', 'Přihlásit se')->getControlPrototype()->class = 'btn btn-primary btn-md pull-right';
        $this->onSuccess[] = array($this, 'formSuccess');

        $this->addFormClass(['margin-bottom-0', 'ajax']);

        return $this;
    }


    public function formSuccess(LoginForm $form)
    {
        $presenter = $this->getPresenter();

        try {
            $values = $form->getValues();
            $user = $presenter->getUser();

            $user->login($values['username'], $values['password']);
            $this->onLoggedIn($this, $user);

            if ($values['remember']) {
                $user->setExpiration('14 days');

            } else {
                $user->setExpiration('2 days', IUserStorage::CLEAR_IDENTITY);
            }


        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
            $presenter->redrawControl();
        }

    }

}