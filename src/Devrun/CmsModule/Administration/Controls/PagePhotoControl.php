<?php
/**
 * This file is part of devrun-advent_calendar.
 * Copyright (c) 2018
 *
 * @file    PagePhotoControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Presenter\TImgStoragePipe;
use Devrun\CmsModule\Controls\AdminControl;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Facades\ImageManageFacade;
use Devrun\CmsModule\Facades\PageFacade;
use Devrun\CmsModule\Forms\DevrunForm;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Devrun\CmsModule\Presenters\PagePresenter;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Nette\Application\Responses\FileResponse;
use Nette\Http\FileUpload;

interface IPagePhotoControlFactory
{
    /** @return PagePhotoControl */
    function create();
}

class PagePhotoControl extends AdminControl
{
    use TImgStoragePipe;

    /** @var ImageManageFacade @inject */
    public $imageManageFacade;


    /** @var PageFacade @inject */
    public $pageFacade;

    /** @var ImagesEntity[] */
    private $images = [];

    /** @var ImagesEntity[] */
    private $assocImages = [];

    /** @var integer id @persistent */
    public $editImageId;


    /** @var ImageRepository @inject */
    public $imageRepository;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var PageEntity */
    private $page;

    /** @var RouteEntity */
    private $route;



    public function QQQsetControlPageRedraw(PagePresenter $presenter)
    {
        $presenter->onPageRedraw[] = function() use ($presenter) {
            if ($presenter->isAjax()) {
                $this->redrawControl('images');
            }
        };

    }



    protected function attached($presenter)
    {
//        dump("attached");


        if ($presenter instanceof PagePresenter) {
            $this->page  = $presenter->getPageEntity();
            $this->route = $presenter->getRouteEntity();

            $presenter->onPageRedraw[] = function() {


                if ($this->presenter->isAjax()) {
                    $this->redrawControl('images');
                }
            };

        }

        parent::attached($presenter);
    }


    public function handleUpdate($id)
    {
        $this->editImageId = $id;

        if (!$this->getImages()[$id]) {
            $this->flashMessage("obrázek nenalezen", 'danger');
            $this->redirect('this');
        }

//        $this->template->image = $image;

        if ($this->presenter->isAjax()) {
            $this->redrawControl('modalForm');

        } else {
            $this->redirect('this');
        }
    }


    public function handleDelete($id)
    {
        if ($image = $this->imageManageFacade->getPageImageJob()->removeImageByID($id)) {
            $this->flashMessage("smazán obrázek $image->name", 'success');

        } else {
            $this->flashMessage("obrázek se nepodařilo smazat", 'danger');
        }

        $this->ajaxRedirect();
    }


    public function handleRemoveNamespace($id)
    {
        $this->imageManageFacade->removeNamespace($id, $this->route);

        /** @var AdminPresenter $presenter */
        $presenter = $this->getPresenter();

        $message = "sekce obrázků [$id] smazána";
        $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Správa obrázků", FlashMessageControl::TOAST_SUCCESS);

        $this->redrawControl('images');
        $presenter->ajaxRedirect('this', null, 'flash');
    }


    public function handleRestore($id)
    {
        if (!$imageEntity = $this->getImages()[$id]) {
            $this->flashMessage("obrázek nenalezen", 'danger');
            $this->redirect('this');
        }

        if (file_exists($referencePath = $imageEntity->getReferenceIdentifier())) {
            if ($imageB = $this->imageManageFacade->getPageImageJob()->updateImageFromIdentifier($imageEntity, $referencePath)) {
                $this->imageRepository->getEntityManager()->flush();
            }
        }

        /** @var AdminPresenter $presenter */
        $presenter = $this->getPresenter();

        $message = "obrázek [$id] obnoven";
        $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Zaslání přístupu", FlashMessageControl::TOAST_INFO);

        $this->template->restoreImage = $id;
        $this->redrawControl('images');
        $presenter->ajaxRedirect('this', null, 'flash');



    }


    public function handleDownload($id)
    {
        /** @var ImagesEntity $image */
        if ($image = $this->imageRepository->find($id)) {
            if (file_exists($filename = $image->getPath())) {
                $this->presenter->sendResponse(new FileResponse($filename, $image->getName()));
            }
        }

        $this->flashMessage("obrázek nenalezen", 'danger');
        $this->ajaxRedirect();
    }






    /**
     * @return ImagesEntity[]
     */
    public function getImages()
    {
        if (!$this->images) {
            if ($this->route) {
                $this->setRoutePhotos();

            } elseif ($this->page) {
                $this->setPagePhotos();

            } else $this->images = [];
        }

        return $this->images;
    }





    public function render()
    {

        $template = $this->getTemplate();

//        $template->images      = $this->images;
        $template->assocImages = $this->getAssocImages();
        $template->title       = $this->page->getName();
        $template->editImageId       = $this->editImageId;
        if ($this->editImageId) {
            $template->editImage = $this->getImages()[$this->editImageId];
        }


        $template->render();
    }


    private function redraw()
    {
        $presenter = $this->getPresenter();

        if ($presenter instanceof PagePresenter) {
            $presenter->onPageRedraw($this);
        }

    }


    private function reload()
    {
        $this->presenter->onPageRedraw[] = function () {
            if ($this->pageEntity->getType() == 'static') {
                $this->redrawControl('staticPageContent');

            } else {
                $this['dynamicPageControl']->redrawControl();
            }

        };
    }



    /**
     * edit image form factory
     *
     * @return \Devrun\CmsModule\Forms\ImageForm
     */
    protected function createComponentImageForm()
    {
        $user = $this->getPresenter()->getUser();
        $images = $this->getImages();
        $editImageId = $this->editImageId;


        $entity = $images[$editImageId];
        $form   = $this->imageManageFacade->imageFormFactory->create();
        $form
            ->setReferenceImage($user->isAllowed(PagePhotoControl::class, 'referenceImage'))
            ->create()
            ->bootstrap3Render()
            ->bindEntity($entity)
            ->setDefaults([
                'name' => $entity->getName(),
                'alt' => $entity->getAlt(),
            ])
            ->onSuccess[] = function (DevrunForm $form, $values) {

            /** @var ImagesEntity $entity */
            $entity = $form->getEntity();

            $imageReady = false;

            /** @var FileUpload $image */
            if ($image = $values->imageUpload) {
                if ($image->isOk() && $image->isImage()) {
                    $imageReady = true;
                }
            }

            if ($imageReady)
                $this->imageManageFacade->getPageImageJob()->updateImageFromUpload($entity, $image);

            else
                $this->imageManageFacade->imageRepository->getEntityManager()->persist($entity);

            $this->imageManageFacade->imageRepository->getEntityManager()->flush();

            $this->flashMessage('Obrázek upraven');
            $this->editImageId = null;
            $this->presenter->payload->modalClose = true;

            if ($this->presenter->isAjax()) {
                $this->redrawControl('flash');
                $this->redrawControl('images');

//                $this->redraw();

                //                $this->redrawControl('modalForm');
//                $this->presenter->redrawControl();
                $this->presenter->redrawControl('staticPageContent');

            } else {
                $this->redirect('this');
            }
        };

        return $form;
    }



    /**
     * @return ImagesEntity[]
     */
    private function getAssocImages()
    {
        $result = [];
        foreach ($this->getImages() as $image) {
            $result[$image->getNamespace()][] = $image;
        }

        return $result;
    }



    /**
     * @return array|PageImageSettings[]
     */
    private function setPagePhotos()
    {
        $this->images = $this->imageRepository->findAssoc(['route.page' => $this->page], 'id');
        return $this->images;
    }


    /**
     * @return ImagesEntity[]
     */
    private function setRoutePhotos()
    {
        $images = $this->imageRepository->createQueryBuilder('e')
            ->addSelect('t')
            ->join('e.translations', 't')
            ->where('e.route = :route')->setParameter('route', $this->route)
            ->getQuery()
            ->getResult();

        foreach ($images as $image) {
            $this->images[$image->id] = $image;
        }

        return $this->images;
    }


}