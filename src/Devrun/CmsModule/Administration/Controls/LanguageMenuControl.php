<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    LanguageMenuControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Kdyby\Translation\Translator;

interface ILanguageMenuControlFactory
{
    /** @return LanguageMenuControl */
    function create();
}

class LanguageMenuControl extends Control
{

    /** @var Translator @inject */
    public $translator;


    public function render()
    {
        $template = $this->getTemplate();
        $template->availableLocales = $this->getAvailableLocales();
        $template->defaultLocale = $this->translator->getDefaultLocale();
        $template->locale = $this->translator->getLocale();

        $template->render();
    }


    /**
     * @return array  (cs_CZ => cs, de_AT => de, sk_SK => sk...)
     */
    protected function getAvailableLocales()
    {
        return array_unique(preg_replace("/^(\w{2})_(.*)$/m", "$1", $this->translator->getAvailableLocales()));
    }


}