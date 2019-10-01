<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ThemeControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Facades\ThemeFacade;
use Devrun\CmsModule\Forms\DevrunForm;
use Devrun\CmsModule\Forms\IDevrunForm;
use Devrun\CmsModule\Forms\IThemeFormFactory;
use Devrun\CmsModule\InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

interface IThemeControlFactory
{
    /** @return ThemeControl */
    function create();
}

/**
 * Class ThemeControl
 *
 * @package Devrun\CmsModule\Controls
 * @method onSuccess($values, string $css)
 */
class ThemeControl extends Control
{

    /** @var ThemeFacade @inject */
    public $themeFacade;

    /** @var IDevrunForm @inject */
    public $devrunForm;

    /** @var EntityManager @inject */
    public $em;



    /** @var PackageEntity */
    private $packageEntity;

    /** @var array Callback */
    public $onSuccess = [];


    public function render()
    {


        $template = $this->createTemplate();
        $template->render();


    }


    protected function createComponentForm($name)
    {
        $form = $this->devrunForm->create();


        $this->themeFacade->settingsFromPackage($this->packageEntity);

        $variableSettings = $this->themeFacade->getVariableSettings();
        $themeVariables   = $this->themeFacade->getThemeVariables();
//        dump($variableSettings);
//        dump($themeVariables);

//        $form = $this->themeFormFactory->create();

        $form->create();

        $form->addGroup('Základní nastavení');

//        $container = $form->addContainer('themeVariables');


        foreach ($variableSettings as $key => $variableSetting) {
            $input = isset($variableSetting['input']) ? $variableSetting['input'] : null;
            $type  = isset($variableSetting['type']) ? $variableSetting['type'] : null;
            $label = isset($variableSetting['label']) ? $variableSetting['label'] : null;

            if ($input == "text") {
                $control = $form->addText($key, $label);

            } elseif ($input == "textArea") {
                $control = $form->addTextArea($key, $label);

            } elseif ($input == "upload") {
                $control = $form->addUpload($key, $label);

            } else {
                continue;
            }

            if ($type) {
                $control->setType($type);

                if ($type == "image") {
                    $control->setAttribute('src', '/pixman/souteze.pixman.cz/web/www/images/pexeso-mini.png');

                } elseif ($type == "range") {
                    $min  = isset($variableSetting['min']) ? $variableSetting['min'] : null;
                    $max  = isset($variableSetting['max']) ? $variableSetting['max'] : null;
                    $step = isset($variableSetting['step']) ? $variableSetting['step'] : null;

                    $control->setAttribute('class', 'slider');
                    if ($min) $control->setAttribute('min', $min);
                    if ($max) $control->setAttribute('max', $max);
                    if ($step) $control->setAttribute('step', $step);
                }

            }


//            $control->setType('color');

            if (isset($variableSetting['validators'])) {
                foreach ($variableSetting['validators'] as $validator => $validatorParams) {
//                    dump($validator);

                    $message = isset($validatorParams['message']) ? $validatorParams['message'] : null;
                    $args    = isset($validatorParams['args']) ? $validatorParams['args'] : null;

                    $control->addRule($validator, $message, $args);
                }
            }

        }

        $form->addSubmit('send', 'Nastavit');

        $form->setDefaults($themeVariables);
//        $form->bindEntity($this->packageEntity);

        $form->bootstrap3Render();
        $form->onSuccess[] = function (DevrunForm $form, $values) {

//            dump($values);
//            dump($form);

            $packageEntity = $this->getPackageEntity();

            $modifyVariables = $this->themeFacade->modifyAndSendVariablesFromForm($values, $packageEntity);
            $modifyVariables = array_merge($packageEntity->getThemeVariables(), $modifyVariables);

            $packageEntity->setThemeVariables($modifyVariables);

            $themeFacade = $this->themeFacade->generateThemeCss();

            $css = $themeFacade->getCss();
            $themeFacade->save();

            $this->onSuccess($packageEntity, $css);
        };

        return $form;
    }


    /**
     * @return PackageEntity
     */
    public function getPackageEntity()
    {
        return $this->packageEntity;
    }

    /**
     * @param PackageEntity $packageEntity
     *
     * @return $this
     */
    public function setPackageEntity($packageEntity)
    {
        $this->packageEntity = $packageEntity;
        return $this;
    }


}