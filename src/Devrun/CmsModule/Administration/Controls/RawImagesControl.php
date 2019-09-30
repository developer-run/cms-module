<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2018
 *
 *
 *
 * @file    RawImagesControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Flame\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\FileUpload;

interface IRawImagesControlFactory
{
    /** @return RawImagesControl */
    function create();
}

class RawImagesControl extends Control
{

    /** @var string patch to /images/ */
    private $imageDir;

    /** @var array images to edit */
    private $images = [];

    private $title = "Title";


    public function render()
    {
        $template = $this->getTemplate();
        $template->title = $this->title;
        $template->images = $this->images;
        $template->render();
    }


    protected function createComponentLoadImage($name)
    {
        $images = $this->images;
//        dump($images);

        return new Multiplier(function ($index) use ($images) {
//            dump($e);
//            dump($images);

            $form = new Form();
            $form->addHidden('index', $index);
            $form->addUpload('image')
                ->setAttribute('class', 'auto-upload')
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE)
                ->addRule(Form::MAX_FILE_SIZE, NULL, 1024 * 1024 * 1);

            $form->getElementPrototype()->setAttribute('class', 'hidden');

            $form->onSuccess[] = function ($form, $values) use ($images) {

//                dump($values);

                /** @var FileUpload $image */
                if (($image = $values->image) && $values->image->isOK() && $values->image->isImage()) {
                    $index = $values->index;



                    $filename = $this->imageDir . $images[$index]['path'] . DIRECTORY_SEPARATOR . $images[$index]['name'];

//                    dump($filename);

//                    dump($image);
//                    die();


//                    dump($images[$values->index]);

                    $image->move($filename);


                }


//                dump($form);
//                dump($values);
//                die();


                $this->redirect('this');
            };

            return $form;
        });
    }


    /**
     * @param array $images
     *
     * @return $this
     */
    public function setImages($images)
    {
        $this->images = $images;
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
     * DI setter
     *
     * @param $dir
     */
    public function setImageDir($dir)
    {
        $this->imageDir = $dir;
    }


}