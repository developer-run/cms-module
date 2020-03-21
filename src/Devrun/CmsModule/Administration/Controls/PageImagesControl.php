<?php
/**
 * This file is part of devrun-care.
 * Copyright (c) 2018
 *
 * @file    PageImagesControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Facades\PageFacade;
use Devrun\CmsModule\Presenters\PagePresenter;
use Devrun\Application\UI\Control\Control;
use Kdyby\Events\Event;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

interface IPageImagesControlFactory
{
    /** @return PageImagesControl */
    function create();
}

/**
 * Class PageImagesControl
 *
 * @package Devrun\CmsModule\Administration\Controls
 * @method onRedirect($changeImage, $image)
 */
class PageImagesControl extends Control
{

    /** @var PageFacade @inject */
    public $pageFacade;

    /** @var PageImagesSettings @inject */
    public $pageImagesSettings;

    /** @var array */
    private $pageImages = [];

    /** @var Event  */
    public $onRedirect = [];

    /** @var PageEntity */
    private $page;


    protected function attached($presenter): void
    {
        if ($presenter instanceof PagePresenter) {
           $this->page = $presenter->getPageEntity();
        }

        parent::attached($presenter);
    }


    public function render($id = null, $route = null)
    {
        dump($id);
        dump($route);

        $template = $this->getTemplate();
        $template->images = $this->pageImages = $this->getPageImages($this->page->getId());

        $template->title = $this->getPageEntity($this->page->getId())->getName();
        $template->render();
    }


    /**
     * @param $name
     *
     * @return Multiplier
     */
    protected function createComponentLoadImage($name)
    {
        // pokud nejsou načteny obrázky z renderu, načteme je pro komponentu dodatečně [po odeslání formuláře]
        if (!$pageImages = $this->pageImages) {

            if (!$id = $this->presenter->getParameter('id')) {
                Debugger::log('parameter id is null, we cant do it!', ILogger::WARNING);

            } else {
                $pageImages = $this->getPageImages($id);
            }
        }

        /** @var PageImageSettings[] $pageImages */
        return new Multiplier(function ($index) use ($pageImages) {
            $form = new Form();
            $form->addHidden('index', $index);
            $form->addUpload('image')
                ->setAttribute('class', 'auto-upload')
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE)
                ->addRule(Form::MAX_FILE_SIZE, NULL, 1024 * 1024 * 1);

            $form->onSuccess[] = function ($form, $values) use ($pageImages) {

                $index       = $values->index;
                $changeImage = $pageImages[$index];
                $filename    = $changeImage->getFileName();

                /** @var FileUpload $imageFileUpload */
                if (($imageFileUpload = $values->image) && $values->image->isOK() && $values->image->isImage()) {

                    $image = $imageFileUpload->toImage();

                    if (($changeImage->getWidth() && $changeImage->getHeight()) && ($image->getWidth() != $changeImage->getWidth() || $image->getHeight() != $changeImage->getHeight())) {
                        $image->resize($changeImage->getWidth(), $changeImage->getHeight());
                        $image->save($filename);

                    } else {
                        $imageFileUpload->move($filename);
                    }

                }

                if ($this->onRedirect->getListeners()) {
                    $this->onRedirect($changeImage, $imageFileUpload);
                }

                // prevent redirect
                $this->redirect('this');
            };

            return $form;
        });
    }


    /**
     * @param $id
     *
     * @return PageEntity
     */
    private function getPageEntity($id)
    {
        /** @var PageEntity $pageEntity */
        static $pageEntity;

        if (!$pageEntity) {
            $pageEntity = $this->pageFacade->getPageRepository()->find($id);
        }

        return $pageEntity;
    }

    /**
     * @param $id
     *
     * @return array|PageImageSettings[]
     */
    private function getPageImages($id)
    {
        if ($page = $this->getPageEntity($id)) {
            return $this->pageImagesSettings->getPageImages($page->getName());
        }
        return [];
    }




}