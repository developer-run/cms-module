<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2016
 *
 * @file    DefaultPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\ArticleModule\Facades\ArticleFacade;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\PageRepository;

class DefaultPresenter extends AdminPresenter
{

    /** @var ImageRepository @inject */
    public $imageRepository;

    /** @var ArticleFacade @inject */
    public $articleFacade;

    /** @var PageRepository @inject */
    public $pageRepository;



    public function renderDefault()
    {
        $selectLangMessageCount = 0;
        $defaultLangMessageCount = 0;

        if ($selectCatalog = $this->translator->getCatalogue()->getFallbackCatalogue()) {
            foreach ($selectCatalog->all() as $item) {
                $selectLangMessageCount += count($item);
            }

            if ($defaultCatalog = $this->translator->getCatalogue()->getFallbackCatalogue()->getFallbackCatalogue()) {
                foreach ($defaultCatalog->all() as $item) {
                    $defaultLangMessageCount += count($item);
                }
            }
        }





        $this->template->selectLangMessageCount = $selectLangMessageCount;
        $this->template->defaultLangMessageCount = $defaultLangMessageCount;
        $this->template->locale = $this->translator->getLocale();

        $this->template->pageCount = $this->pageFacade->getPageRepository()->countBy();
        $this->template->imageCount = $this->imageRepository->countBy();
        $this->template->articleCount = $this->articleFacade->getArticleRepository()->countBy();

        $this->template->installPackageControls = $this->administrationManager->getAdministrationComponentsByCategory('Dashboard');
        $this->template->instancePackageControls = $this->administrationManager->getAdministrationComponentsByCategory('Instance');




    }

}