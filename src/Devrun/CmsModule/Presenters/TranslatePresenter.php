<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    TranslatePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Facades\TranslateFacade;
use Devrun\CmsModule\TranslateException;
use Nette;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

class TranslatePresenter extends AdminPresenter
{

    /** @var TranslateFacade @inject */
    public $translateFacade;

    /** @var array */
    private $translateList = [];

    /** @var array */
    private $translateData = [];


    public function handleDelete($id)
    {
        dump("delete");

        dump($id);
        die();


    }


    /**
     * @param $domain
     * @param $translateId
     * @param $content
     *
     * @throws Nette\Application\AbortException
     * @throws Nette\Utils\AssertionException
     *
     * @example
     *  $catalogue = $this->translator->getCatalogue();
     *  $localeCatalog = $catalogue->getFallbackCatalogue();
     *
     *  foreach ($localeCatalog->getResources() as $resource => $fileResource) {
     *      dump($resource);
     *      dump($fileResource->getResource());
     *  }
     *
     */
    public function renderUpdate($domain, $translateId, $content)
    {
        Nette\Utils\Validators::assert($domain, 'string', __METHOD__ . " `domain` ");
        Nette\Utils\Validators::assert($translateId, 'string', __METHOD__ . " `translateId` ");

        try {
            $translate = $this->translateFacade->updateTranslate($domain, $translateId, $content);
            $this->payload->translate = $translate;

        } catch (TranslateException $e) {
            // @todo flash message not work correctly, because other process?
            $this->flashMessage($e->getMessage(), FlashMessageControl::TOAST_TYPE, "Translate error", FlashMessageControl::TOAST_WARNING);
            $this->ajaxRedirect('this', null, ['flash']);
            return;
        }

        $this->sendPayload();
    }


    public function renderDefault()
    {
        $domains = $this->translateFacade->getDefaultDomains();
        $this->translateData = $this->translateFacade->getTranslateData();

        $this->template->domains = $domains;
    }


    protected function createComponentTranslateControl($name)
    {
        $translationData = $this->translateData;

//        dump($name);

        return new Nette\Application\UI\Multiplier(function ($translationId) use ($translationData, $name) {
//            dump($translationId);
//            dump($this);

//        $grid = new DataGrid($this, $name);
//        $grid = new DataGrid($this, $translationId);
//            $grid = new DataGrid($this, $name);
            $grid = new DataGrid();



//            $translationId = 'site';
            //            $grid->translator->lang = 'cs';

//            $grid->model = new \Grido\DataSources\ArraySource($data);
            $data = $this->translateFacade->getTranslateData($translationId);

            $grid->setDataSource($data);
//            dump($data);

            $grid->addColumnText('id', 'ID')
                ->setFilterText();

            $grid->addColumnText('defaultValue', "Výchozí ({$this->translator->getDefaultLocale()})")
                ->setFilterText();

            $grid->addColumnText('localeValue', "Překlad ({$this->translator->getLocale()})")
                ->setRenderer(function (array $row) use ($translationId) {
                    $result = Html::el('p')
                        ->setHtml($row['localeValue'])
                        ->setAttribute('contenteditable', 'true')
                        ->setAttribute('data-domain', $translationId)
                        ->setAttribute('data-translate', $row['id']);
                    return $result;
                })
                ->setFilterText();

            return $grid;

/*
            $data = $this->translateFacade->getTranslateData($translationId);

            $grid = new DataGrid($this, $name);
//            $grid->translator->lang = 'cs';

//            $grid->model = new \Grido\DataSources\ArraySource($data);

            $grid->addColumnText('id', 'ID')
                ->setFilterText();

            $grid->addColumnText('defaultValue', "Výchozí ({$this->translator->getDefaultLocale()})")
                ->setFilterText();

            $grid->addColumnText('localeValue', "Překlad ({$this->translator->getLocale()})")
//                ->setCustomRender(function (array $row) use ($translationId) {
//                    $return = html_entity_decode(Html::el('p')
//                        ->setHtml($row['localeValue'])
//                        ->setAttribute('contenteditable', 'true')
//                        ->setAttribute('data-domain', $translationId)
//                        ->setAttribute('data-translate', $row['id']));
//
//                    return $return;
//                })
                ->setFilterText();

//            $grid->filterRenderType = Filter::RENDER_INNER;
            return $grid;
*/
        });
    }


    protected function createComponentTranslateControls()
    {
        $translationList = $this->translateList;

        return new Nette\Application\UI\Multiplier(function ($translationId) use ($translationList) {

            // transform _ to .
            $originalTranslationId = str_replace('_', '.', $translationId);
            $humanTranslationId = str_replace('_', ' ', $translationId);

            $grid = new Grid();
            $grid->translator->lang = 'cs';
            $grid->setDefaultPerPage(5);

            $data = [];
            foreach ($translationList[$translationId] as $key => $text) {
                $data[] = [
                    'id'      => $key,
                    'content' => $text,
                ];
            }

            $grid->model = new \Grido\DataSources\ArraySource($data);

            $grid->addColumnText('content', $humanTranslationId)
                ->setCustomRender(function (array $row) use ($grid, $originalTranslationId) {
                    $return = html_entity_decode(Html::el('p')
                        ->setHtml($row['content'])

                        ->setAttribute('contenteditable', 'true')
                        ->setAttribute('data-translate', "$originalTranslationId.{$row['id']}"));


                    return $return;
                })
                ->setFilterText();


            $grid->addActionHref('delete', 'Smazat', 'delete!')
                ->setIcon('trash fa-2x')
                ->setConfirmation(new StringConfirmation("Opravdu chcete smazat článek id: %s?", 'id'))
                ->setCustomRender(function ($row , Html $el) use ($originalTranslationId) {
                    $el->href($this->link('delete!', ['id' => "$originalTranslationId.{$row['id']}"]));
                    return $el;
                })
                ->getElementPrototype()->addAttributes(array(
                    'class' => 'ajax',
                ));

            return $grid;

        });


    }


}