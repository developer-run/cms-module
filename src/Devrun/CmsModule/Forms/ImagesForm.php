<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImagesForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

/**
 * Interface IImagesFormFactory
 *
 * @package Devrun\CmsModule\Forms
 */
interface IImagesFormFactory
{
    /** @return ImagesForm */
    function create();
}

use Nette\Application\UI\Form;

/**
 * Class ImagesForm
 *
 * @package Devrun\CmsModule\Forms
 */
class ImagesForm extends DevrunForm implements IImagesFormFactory
{

    /** @var bool */
    private $displayName = true;

    /** @var bool */
    private $displaySystemName = true;

    /** @var bool */
    private $displayNamespace = true;

    /** @var bool */
    private $displayAlt = true;

    /** @var bool */
    private $displayFileUpload = false;

    /** @var bool */
    private $displayFileUploads = true;

    /**
     * @return ImagesForm
     */
    public function create()
    {
        if ($this->displayName) {
            $this->addText('name', 'Název')
                ->setHtmlAttribute('placeholder', "Název")
                ->addCondition(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, NULL, 128);
        }

        if ($this->displaySystemName) {
            $this->addText('systemName', 'Systémový název')
                ->setHtmlAttribute('placeholder', "Název pro systém")
                ->addRule(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, NULL, 64);
        }

        if ($this->displayNamespace) {
            $this->addText('namespace', 'Kategorie')
                ->setHtmlAttribute('placeholder', "Název kategorie")
                ->addCondition(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, NULL, 128);
        }

        if ($this->displayAlt) {
            $this->addText('alt', 'Alt název')
                ->setHtmlAttribute('placeholder', "Alternative název")
                ->addCondition(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, NULL, 255);
        }

        if ($this->displayFileUploads) {
            $this->addMultiUpload('filenames', 'Obrázky')
                ->addRule(Form::MAX_FILE_SIZE, NULL, 1024*1024*2)
                ->setRequired();
        }

        if ($this->displayFileUpload) {
            $this->addUpload('filename', 'Obrázek')
                ->setRequired();
        }


        $this->addSubmit('send', 'Odeslat');


        return $this;
    }

    /**
     * @param bool $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * @param bool $displayNamespace
     */
    public function setDisplayNamespace($displayNamespace)
    {
        $this->displayNamespace = $displayNamespace;
        return $this;
    }

    /**
     * @param bool $displayAlt
     */
    public function setDisplayAlt($displayAlt)
    {
        $this->displayAlt = $displayAlt;
        return $this;
    }

    /**
     * @param bool $displayFileUpload
     */
    public function setDisplayFileUpload($displayFileUpload)
    {
        $this->displayFileUpload = $displayFileUpload;
        return $this;
    }

    /**
     * @param bool $displayFileUploads
     */
    public function setDisplayFileUploads($displayFileUploads)
    {
        $this->displayFileUploads = $displayFileUploads;
        return $this;
    }

    /**
     * @param bool $displaySystemName
     */
    public function setDisplaySystemName($displaySystemName)
    {
        $this->displaySystemName = $displaySystemName;
        return $this;
    }




}