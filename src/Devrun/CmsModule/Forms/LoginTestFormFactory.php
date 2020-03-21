<?php


namespace Devrun\CmsModule\Forms;

use Devrun\DoctrineModule\DoctrineForms\EntityFormTrait;
use Nette\Application\UI\Form;

class LoginTestFormFactory
{

    use EntityFormTrait;


    public function create(): Form
    {
        $form = new Form;

        $form->addText('name', 'Jméno:');
        // ...
        $form->addSubmit('login', 'Přihlásit se');

        return $form;


    }


}