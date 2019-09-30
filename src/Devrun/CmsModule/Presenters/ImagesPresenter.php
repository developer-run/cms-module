<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ImagesPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Brabijan\Images\ImageStorage;
use Brabijan\Images\TImagePipe;
use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Facades\ImageManageFacade;
use Devrun\CmsModule\Forms\DevrunForm;
use Devrun\CmsModule\Forms\IImagesFormFactory;
use Devrun\CmsModule\Repositories\ImageRepository;
use Nette\Application\Responses\FileResponse;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use PHPZip\Zip\File\Zip;

class ImagesPresenter extends AdminPresenter
{

    /** @var ImageManageFacade @inject */
    public $imageManageFacade;

    /** @var ImageRepository @inject */
    public $imageRepository;

    /** @var ImagesEntity */
    private $imageEntity;

    /** @var int @persistent */
    public $viewTable;


    protected $enableAjaxLayout = false;



    protected function afterRender()
    {
    }


    /**
     * toggle view table
     */
    public function handleViewTable()
    {
        $this->viewTable = $this->viewTable ? 0 : 1;

//        $this->flashMessage("sekce obrázků [$id] smazána", 'success');
        $this->ajaxRedirect();
    }

    public function handleRemoveNamespace($id = '')
    {
        $this->imageManageFacade->removeImages($id);

        $this->flashMessage("sekce obrázků [$id] smazána", 'success');
        $this->ajaxRedirect();
    }

    /**
     * remove only fyzic files, not database
     *
     * @param $id
     */
    public function handleRemoveOnlyImageNamespace($id)
    {
        $this->imageManageFacade->getImageService()->removeNamespace($id);

        $this->flashMessage("sekce obrázků [$id] smazána", 'success');
        $this->ajaxRedirect();
    }


    /**
     * remove image
     *
     * @param $id
     */
    public function handleDelete($id)
    {
        if ($image = $this->imageManageFacade->removeImage($id)) {
            $this->flashMessage("smazán obrázek $image->name", 'success');

        } else {
            $this->flashMessage("obrázek se nepodařilo smazat", 'danger');
        }

        $this->ajaxRedirect();
    }


    public function handleDownload($id)
    {
        /** @var ImagesEntity $image */
        if ($image = $this->imageRepository->find($id)) {
            if (file_exists($filename = $image->getPath())) {
                $this->sendResponse(new FileResponse($filename, $image->getName()));
            }
        }

        $this->flashMessage("obrázek nenalezen", 'danger');
        $this->ajaxRedirect();
    }


    /**
     * download section
     *
     * @param      $id
     * @param null $path
     */
    public function handleDownloadNamespace($id, $path = null)
    {
        /** @var ImagesEntity[] $images */
        if ($images = $this->imageRepository->findBy(['namespace' => $id])) {
            $zip = new Zip();

            $savedPaths = [];
            foreach ($images as $image) {
                if (file_exists($filePath = $image->getPath())) {
                    $savePath = $path == null
                        ? $image->getName()
                        : $image->getPath();

                    if (!in_array($savePath, $savedPaths)) {
                        $savedPaths[] = $savePath;
                        $file = file_get_contents($filePath);
                        $zip->addFile($file, $savePath);
                    }
                }
            }

            $saveName = $path ? "cms-$id-prefixed.zip" : "cms-$id.zip";

            $zip->finalize();
            $zip->setZipFile($saveName);
            $zip->sendZip($saveName, "application/zip", $saveName);
        }

        $this->flashMessage("obrázek nenalezen", 'danger');
        $this->ajaxRedirect();
    }


    public function renderDefault()
    {
        $this->template->assocImages = $this->imageManageFacade->imageRepository->getAssocImages();
        $this->template->viewTable = $this->viewTable;

//        dump($this->template->assocImages);
//        die();

    }


    public function actionUpdate($id, $namespace = '')
    {
        if (!$id || !$imageEntity = $this->imageManageFacade->imageRepository->find($id)) {
            $imageEntity = new ImagesEntity($this->translator);
        }

        if ($namespace) {
            $imageEntity->namespace = $namespace;
        }

        if ($id == null) {
            $this->template->images = $imageEntity;
        } else {
            $this->template->image = $imageEntity;
        }

    }


    /**
     * mass upload new images
     *
     * @return \Devrun\CmsModule\Forms\ImagesForm
     */
    protected function createComponentImagesForm()
    {
        $entity = $this->template->images;
        $form   = $this->imageManageFacade->imagesFormFactory->create();
        $form->setDisplayName(false)->setDisplaySystemName(false)->setDisplayAlt(false)->create();

        $form
            ->bootstrap3Render()
            ->bindEntity($entity)
            ->onSuccess[] = function (DevrunForm $form, $values) {

            if ($entity = $form->getEntity()) {

                try {
                    $this->imageManageFacade->getImageService()->getImageStorage()->onUploadImage[] = function ($path, $namespace) {

                    };

                    $this->imageManageFacade->onPostUploadImage[] = function ($image, $uploadImage, $namespace, $filename) {

                    };

                    // if namespace is not defined, upload directly to dir
                    $namespace = isset($values->namespace) ? $values->namespace : null;

                    $this->imageManageFacade->addImages($values->filenames, $namespace);

//                    die("END");

                } catch (InvalidArgumentException $e) {
                    $this->flashMessage("Nepodařilo se nahrát {$e->getMessage()}");
                    $this->ajaxRedirect();
                }

                $this->flashMessage('Obrázky nahrány');
                $this->ajaxRedirect("default");
            }

        };

        return $form;
    }


    /**
     * edit image form factory
     *
     * @return \Devrun\CmsModule\Forms\ImageForm
     */
    protected function createComponentImageForm()
    {
        $entity = $this->template->image;
        $form   = $this->imageManageFacade->imageFormFactory->create();

        $form->create()
            ->bootstrap3Render()
            ->bindEntity($entity)
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
                $this->imageManageFacade->updateImageFromEntity($entity, $image);

            else
                $this->imageManageFacade->imageRepository->getEntityManager()->persist($entity)->flush();

            $this->flashMessage('Obrázek upraven');
            $this->ajaxRedirect("default");
        };


        return $form;
    }


    protected function createComponentImagesControlGrid($name)
    {
        $grid = new DataGrid();
        $grid->setTranslator($this->translator);

        $model = $this->imageRepository->createQueryBuilder('e');


//        dump($model->getQuery()->getResult());
//        die();


        $grid->setDataSource($model);


        $grid->addColumnText('systemName', 'Jméno')
            ->setFitContent()
            ->setSortable()
            ->setFilterText();


        $grid->addColumnText('namespace', 'Namespace')
            ->setFitContent()
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('route', 'Title', 'route.title')
            ->setFitContent()
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('qw', 'Title', 'route.uri')
            ->setFitContent()
            ->setSortable()
            ->setFilterText();


        $grid->addColumnText('image', 'Img')
            ->setTemplate(__DIR__ . '/templates/Images/Datagrid/grid.img.latte', ['_imagePipe' => $this->imgPipe]);



//        $grid->addColumnText('gtmName', 'GTM Jméno')
//            ->setEditableCallback(function($id, $value) use ($grid) {
//                if (Validators::is($value, $validate = 'string:4..32')) {
//                    if ($entity = $this->settingsCardsRepository->find($id)) {
//                        $entity->gtmName = $value;
//                        $this->settingsCardsRepository->getEntityManager()->persist($entity)->flush();
//                        return true;
//                    }
//                }
//                $message = "input not valid [$value != $validate]";
//                return $grid->invalidResponse($message);
//            })
//            ->setSortable()
//            ->setFilterText();
//
//        $grid->addColumnText('benefitName', 'Název benefitu')
//            ->setSortable()
//            ->setEditableCallback(function($id, $value) {
//                if (Validators::is($value, 'string:4..32')) {
//                    if ($entity = $this->settingsCardsRepository->find($id)) {
//                        $entity->benefitName = $value;
//                        $this->settingsCardsRepository->getEntityManager()->persist($entity)->flush();
//                        return true;
//                    }
//                }
//
//                return false;
//            })
//            ->setFilterText();
//
//        $grid->addColumnText('localeValue', 'Popis benefitu (html nadpis \n small)')
//            ->setRenderer(function ($row) {
//                $localValue = $this->translator->domain($domain = 'pexeso')->translate($translateId = "results.{$row->name}");
//
//                $result = Html::el('p')
//                    ->setHtml($localValue)
//                    ->setAttribute('contenteditable', 'true')
//                    ->setAttribute('data-domain', $domain)
//                    ->setAttribute('data-translate', $translateId);
//                return $result;
//            });
//
//
//        $statusList = array(null => 'Všechny', '0' => 'Neaktivní', '1' => 'Aktivní');
//
//        $grid->addColumnStatus('active', 'Stav')
//            ->setFitContent()
//            ->addOption(0, 'Neaktivní')
//            ->setClass('btn-default')
//            ->endOption()
//
//            ->addOption(1, 'Aktivní')
//            ->setClass('btn-success')
//            ->endOption()
//            ->setSortable()
//            ->setFilterSelect($statusList);
//
//        $grid->getColumn('active')
//            ->onChange[] = function ($id, $new_value) use ($grid) {
//
//            /** @var SettingsCardsEntity $entity */
//            $entity = $this->settingsCardsRepository->find($id);
//            $entity->setActive($new_value);
//            $this->settingsCardsRepository->getEntityManager()->persist($entity)->flush();
//
//            $this->flashMessage('Record was added!', 'success');
//            $this->redrawControl('flashes');
//
//            $grid->reload();
//        };


//        $grid->addAction('delete', 'Smazat', 'delete!')
//            ->setIcon('trash fa-2x')
//            ->setClass('ajax btn btn-xs btn-danger')
//            ->setConfirm(function ($item) {
//                return "Opravdu chcete smazat záznam [id: {$item->id}; {$item->createdBy->email}]?";
//            });


        $grid->addInlineAdd()
            ->onControlAdd[] = function (Container $container) {

            $container->addText('name', '');
            $container->addText('benefitName', '');
        };

        $p = $this;

        $grid->getInlineAdd()->onSubmit[] = function($values) use ($p, $grid) {
            /**
             * Save new values
             */
            $entity = new SettingsCardsEntity();

            foreach ($values as $key => $value) {
                $entity->$key = $value;
            }

            $this->settingsCardsRepository->getEntityManager()->persist($entity)->flush();

            /**
             * Save new values
             */
            $this->flashMessage('Record was updated! (not really)', 'success');
            $this->redrawControl('flashes');
            $p->redrawControl('flashes');

            $grid->reload();
        };

        /*        $grid->addInlineEdit()->setText('edit')
                    ->onControlAdd[] = function(Container $container) {

                    $container->addText('id', '');
                    $container->addText('value', '');
                    $container->addText('validate', '');
                };*/

        $grid->getInlineEdit()->onSubmit[] = function($id, $values) {

            if (!$entity = $this->settingsCardsRepository->find($id)) {
                $this->flashMessage('Record was not found', 'danger');
                $this->redrawControl('flashes');
                return false;

            } else {
                foreach ($values as $key => $value) {
                    $entity->$key = $value;
                }
            }

            /**
             * Save new values
             */
            $this->flashMessage('Record was updated!', 'success');
            $this->redrawControl('flashes');
            $this->em->persist($entity)->flush();

            return true;
        };



        return $grid;
    }



}