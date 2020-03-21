<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    PackageSelectForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\DoctrineModule\DoctrineForms\IComponentMapper;
use Nette\Application\UI\Form;

interface IPackageSelectFormFactory
{
    /** @return PackageSelectForm */
    function create();
}

class PackageSelectForm extends DevrunForm
{

    private $packages = [];


    public function create()
    {
        $this->addGroup('Výběr');

        $this->addSelect('package', 'Balíček', $this->packages)
            ->setAttribute('placeholder', "Vyberte balíček")
            ->setOption(IComponentMapper::ITEMS_TITLE, 'name')
            ->addRule(Form::FILLED)
            ->getControlPrototype()->addAttributes(['class' => 'auto-change']);


//        $this->addSubmit('send', 'Odeslat')
//            ->setAttribute('data-dismiss', 'modal');

//        $this->addFormClass(['ajax']);

        return $this;
    }

    /**
     * @param array $packages
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;
    }





}