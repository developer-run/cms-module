<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2018
 *
 * @file    SettingsControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Entities\SettingsEntity;
use Devrun\CmsModule\Facades\SettingsFacade;
use Nette\Forms\Container;
use Nette\Localization\ITranslator;
use Nette\Utils\Validators;

interface ISettingsControlFactory
{
    /** @return SettingsControl */
    function create();
}

class SettingsControl extends Control
{

    /** @var ITranslator @inject */
    public $translator;

    /** @var SettingsFacade @inject */
    public $settingsFacade;

    /** @var array of keys */
    private $keys = [];

    private $title = 'Nastavení';

    private $inBox = true;


    public function render()
    {
        $template = $this->getTemplate();
        $template->title = $this->title;
        $template->inBox = $this->inBox;
        $template->render();
    }



    protected function createComponentSettingsControlGrid($name)
    {
        $class = SettingsControl::class;
        $presenter = $this->getPresenter();

        $grid = new DataGrid();
        $grid->setTranslator($this->translator);

        $keys = $this->keys;

        $model = $this->settingsFacade->getRepository()->findBy(['id' => $keys]);

        $grid->setDataSource($model);

        $id = $grid->addColumnText('id', 'Id')
            ->setSortable();

        if ($presenter->getUser()->isAllowed($class, 'editSettings')) {
            $id->setEditableCallback(function($id, $value) {
                if (Validators::is($value, 'string:1..128')) {
                    /** @var SettingsEntity $entity */
                    if ($entity = $this->settingsFacade->getRepository()->find($id)) {
                        $entity->setId($value);
                        $this->settingsFacade->getRepository()->getEntityManager()-> persist($entity)->flush();
                        return true;
                    }
                }

                return false;
            });

        } else {
            $id->setRenderer(function (SettingsEntity $entity) {
                return $this->translator->translate("{$entity->getId()}");
            });
        }

        $id->setFilterText();



        $grid->addColumnText('value', 'Value')
            ->setEditableCallback(function($id, $value) use ($grid) {
                $message = "";

                /** @var SettingsEntity $entity */
                if ($entity = $this->settingsFacade->getRepository()->find($id)) {
                    $validate = $entity->getValidate();

                    if (Validators::is($value, $validate)) {
                        $entity->setValue($value);
                        $this->settingsFacade->getRepository()->getEntityManager()-> persist($entity)->flush();
                        return true;
                    }
                    $message = "input not valid [$value != $validate]";
                }

                return $grid->invalidResponse($message);
            })
            ->setSortable()
            ->setFilterText();

        if ($presenter->getUser()->isAllowed($class, 'editSettings')) {
            $grid->addColumnText('validate', 'Validate')
                ->setEditableCallback(function($id, $value) {
                    if (Validators::is($value, 'string:1..128')) {
                        /** @var SettingsEntity $entity */
                        if ($entity = $this->settingsFacade->getRepository()->find($id)) {
                            $entity->setValidate($value);
                            $this->settingsFacade->getRepository()->getEntityManager()-> persist($entity)->flush();
                            return true;
                        }
                    }

                    return false;
                })
                ->setSortable()
                ->setFilterText();


            $grid->addInlineAdd()
                ->onControlAdd[] = function (Container $container) {

                $container->addText('id', '');
                $container->addText('value', '');
                $container->addText('validate', '');
            };

            $p = $this;

            $grid->getInlineAdd()->onSubmit[] = function($values) use ($p, $grid) {
                /**
                 * Save new values
                 */

                $this->settingsFacade->save($values['id'], $values['value']);

                /**
                 * Save new values
                 */
                $this->flashMessage('Record was updated! (not really)', 'success');
                $this->redrawControl('flashes');
                $p->redrawControl('flashes');

                $grid->reload();
            };


        }

        return $grid;
    }



    /**
     * @param array $keys
     *
     * @return $this
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param bool $inBox
     *
     * @return $this
     */
    public function setInBox($inBox)
    {
        $this->inBox = $inBox;
        return $this;
    }





}