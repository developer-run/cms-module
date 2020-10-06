<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ProfileForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\CmsModule\Repositories\DomainRepository;
use Kdyby\Translation\Phrase;
use Nette\Application\UI\Form;
use Nette\Utils\Html;

interface IProfileFormFactory
{
    /** @return ProfileForm */
    function create();
}

class ProfileForm extends DevrunForm
{

    const EVENT_SUCCESS = "Devrun\CmsModule\Forms\ProfileForm::onSuccess";


    /** @var DomainRepository @inject */
    public $domainRepository;


    public function create()
    {
        $this->addGroup('profile');

        $this->addText('nickname', 'nickname')
            ->setHtmlAttribute('placeholder', "placeholder.nickname")
            ->addCondition(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 2), 2)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addRadioList('gender', 'gender', ['woman', 'man']);

        $this->addText('firstName', 'first_name')
            ->setHtmlAttribute('placeholder', "placeholder.first_name")
            ->addRule(Form::FILLED, 'ruleFirstName')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('lastName', 'last_name')
            ->setHtmlAttribute('placeholder', "placeholder.last_name")
            ->addRule(Form::FILLED, 'ruleLastName')
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('email', 'email')
            ->setHtmlAttribute('placeholder', "placeholder.email")
            ->addRule(Form::FILLED, 'ruleEMail')
            ->addRule(Form::EMAIL, 'valid_email');

        $this->addText('phone', 'phone')
            ->setHtmlAttribute('placeholder', "placeholder.phone");

        $this->addGroup('address');

        $this->addText('street', 'street')
            ->setHtmlAttribute('placeholder', "placeholder.street")
            ->addCondition(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('city', 'city')
            ->setHtmlAttribute('placeholder', "placeholder.city")
            ->addCondition(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, new Phrase('ruleMinLength', 3), 3)
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $this->addText('psc', 'psc')
            ->setHtmlAttribute('placeholder', "placeholder.psc")
            ->addCondition(Form::FILLED)
            ->addRule(Form::NUMERIC, 'valid_numeric');




//        $privacy = $this->addCheckbox('privacy', 'privacy');
//        $privacy->getLabelPart()->addHtml(Html::el('p')->setText('asdd'));
//        $privacy->getLabelPrototype()->addHtml(Html::el('p'));



        $this->addSubmit('send', 'save')->setHtmlAttribute('class', 'btn btn-primary btn-md');
//        $this->onSuccess[] = array($this, 'formSuccess');

        return $this;
    }


    private function getDomainList()
    {
        return $this->domainRepository->findPairs([], 'name', []);
    }

}