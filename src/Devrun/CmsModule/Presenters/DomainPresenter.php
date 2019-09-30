<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    DomainPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;


use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\DomainEntity;
use Devrun\CmsModule\Facades\DomainFacade;
use Devrun\CmsModule\Facades\PackageFacade;
use Devrun\CmsModule\Repositories\DomainRepository;
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\Utils\Pattern;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Utils\Html;
use Nette\Utils\Validators;

class DomainPresenter extends AdminPresenter
{
    /** @var DomainRepository @inject */
    public $domainRepository;

    /** @var PackageFacade @inject */
    public $packageFacade;

    /** @var DomainFacade @inject */
    public $domainFacade;


    private function writeValidDomainsToFile()
    {
        $default = ["souteze.pixman.cz"];
        $validDomains = $this->domainRepository->findPairs(['valid' => true], 'name');
        $validDomains = array_merge($default, $validDomains);

        $validator = $this->domainFacade->getValidator();
        $validator->setValidDomains($validDomains);
    }


    public function handleDelete($id)
    {
        /** @var DomainEntity $entity */
        if (!$entity= $this->domainRepository->find($id)) {
            $this->flashMessage('Záznam nenalezen', 'danger');
            $this->ajaxRedirect();
        }

        $this->domainRepository->getEntityManager()->remove($entity)->flush();
        $this->flashMessage("Záznam {$entity->getName()} smazán", 'success');

        $this->writeValidDomainsToFile();

        if ($this->isAjax()) {
            $this->redrawControl('flash');
            $this['gridControl']->reload();
        }

        $this->ajaxRedirect();
    }

    public function handleCheckDomain($id)
    {
        /** @var DomainEntity $entity */
        if (!$entity = $this->domainRepository->find($id)) {
            $this->flashMessage('Záznam nenalezen', 'danger');
            $this->ajaxRedirect();
        }

        $validator = $this->domainFacade->getValidator();
        $entity->setValid($valid = $validator->isValidDomain($domain = $entity->getName()));
        $this->domainRepository->getEntityManager()->persist($entity)->flush();

        if ($valid) {
            //$this->packageFacade->getPackageDomain()->addValidDomain($domain);
            $message = "Doména `{$entity->getName()}` validní";

        } else {
            $message = "Doména `{$entity->getName()}` nevalidní";

        }

        $this->writeValidDomainsToFile();

        $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Správa domén", FlashMessageControl::TOAST_SUCCESS);

        if ($this->presenter->isAjax()) {
            $this['gridControl']->reload();
        }

        $this->ajaxRedirect('this', null, ['flash']);
    }




    protected function createComponentGridControl($name)
    {
        $grid =  new DataGrid();
        $grid->setTranslator($this->translator);

        $query = $this->domainRepository->createQueryBuilder('a')
            ->where('a.id > 0');


        if (!$this->user->isAllowed("Cms:Page", 'editAllPackages')) {
            $query->andWhere('a.user = :user')->setParameter('user', $this->user->id);
        }


        $grid->setDataSource($query);

        $grid->addColumnDateTime('inserted', 'Záznam vložen')
            ->setAlign('text-left')
            ->setSortable()
            ->setFitContent()
            ->setFilterDateRange();

        $grid->addColumnDateTime('name', 'Název domény')
            ->setAlign('text-left')
            ->setSortable()
            ->setRenderer(function (DomainEntity $domainEntity) {
                $html = Html::el('span')
                    ->setText(" " . $domainEntity->getName());

                if (true === $valid = $domainEntity->isValid()) {
                    $html->setAttribute('class', 'fa fa-check fa-1x text-success');

                } else {
                    $html->setAttribute('class', 'fa fa-times fa-1x text-danger');
                }

                return $html;
            })
            ->setFilterText();

        $grid->addInlineAdd()
            ->setPositionTop()
            ->onControlAdd[] = function (Container $container)  {
//            $container->addText('id')->setAttribute('readonly');//->setValue($this->editTemplateId);
            $container->addText('name')->addRule(Form::REQUIRED)->addRule(Form::PATTERN, 'vyplňte prosím správně doménu', Pattern::URL);
        };

        $grid->addAction('check', 'Ověřit', 'checkDomain!')
            ->setIcon('check fa-1x')
            ->setClass('ajax btn btn-xs btn-default');

        $grid->addAction('delete', 'Smazat', 'delete!')
            ->setIcon('trash fa-2x')
            ->setClass('ajax btn btn-xs btn-danger')
            ->setConfirm(function ($item) {
                return "Opravdu chcete smazat záznam [id: {$item->id}; {$item->name}]?";
            });



        $p = $this;

        $grid->getInlineAdd()->onSubmit[] = function($values) use ($p) {

            /**
             * Save new values
             */

            /** @var DomainEntity $entity */
            $entity = new DomainEntity($values->name);
            $entity->setUser($this->getUserEntity());

            $em = $this->domainRepository->getEntityManager();
            $em->persist($entity)->flush();

            $message = "Record with values [{$entity->getName()}] was added!";
            $p->flashMessage($message, FlashMessageControl::TOAST_TYPE, 'Správa uživatelů', FlashMessageControl::TOAST_SUCCESS);

            $this['gridControl']->reload();
            $this->ajaxRedirect('this', null, ['flash']);
        };

        $grid->addInlineEdit()->setText('Edit')
            ->onControlAdd[] = function(Container $container) {

            $container->addText('name', '')
                ->setAttribute('placeholder', 'domain.cz | www.domain.cz')
                ->addRule(Form::FILLED)
                ->addRule(Form::PATTERN, 'Zadejte prosím adresu ve tvaru `domain.cz [www.domain.cz]`', Pattern::URL);
        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, DomainEntity $item) {

            $container->setDefaults([
                'id' => $item->id,
                'name' => $item->getName(),
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function($id, $values) {

            /** @var DomainEntity $entity */
            if ($entity= $this->domainRepository->find($id)) {
                $entity
                    ->setName($values->name)
                    ->setValid(false);

                $this->domainRepository->getEntityManager()->persist($entity)->flush();
                $this->writeValidDomainsToFile();

                $this->domainFacade->onChangeDomain($entity);
            }
        };

        return $grid;
    }


}