<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImageForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\Utils\PhpInfo;
use Nette\Application\UI\Form;

interface IImageFormFactory
{
    /** @return ImageForm */
    function create();
}

class ImageForm extends DevrunForm
{

    /** @var bool */
    private $referenceImage = false;


    public function create()
    {
        $identify = $this->addContainer('identify');


        $this->addText('name', 'Název')
            ->setHtmlAttribute('placeholder', "Název")
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, NULL, 128);

        $this->addText('alt', 'Alt název')
            ->setHtmlAttribute('placeholder', "Alternative název")
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, NULL, 255);

        $identify->addText('name', 'Systémový název')
            ->setHtmlAttribute('placeholder', "Název pro systém")
            ->setHtmlAttribute('readonly', true)
            ->addRule(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, NULL, 64);

        $identify->addText('namespace', 'Kategorie')
            ->setHtmlAttribute('placeholder', "Název kategorie")
            ->setHtmlAttribute('readonly', true)
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, NULL, 128);

        if ($this->referenceImage) {
            $identify->addText('referenceIdentifier', 'Referenční obrázek');
        }

        $this->addUpload('imageUpload', 'Obrázek')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_FILE_SIZE, NULL, PhpInfo::file_upload_max_size());



        $this->addSubmit('send', 'Odeslat')
            ->setHtmlAttribute('data-dismiss', 'modal');


        $this->addFormClass(['ajax']);

        return $this;
    }


    /**
     * @param bool $referenceImage
     *
     * @return $this
     */
    public function setReferenceImage(bool $referenceImage)
    {
        $this->referenceImage = $referenceImage;
        return $this;
    }




}