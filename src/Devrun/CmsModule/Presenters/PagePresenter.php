<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    PagePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\IPackageControlFactory;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Repositories\ImageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Nette;
use Tracy\Debugger;

/**
 * Class PagePresenter
 *
 * @package Devrun\CmsModule\Presenters
 * @method onPageRedraw($control);
 * @method onPageEdit(PageEntity $page, RouteEntity & $route, $presenter);
 */
class PagePresenter extends AdminPresenter
{


    /** @var int _@__persistent */
    public $routeSelect;

    /** @var int @persistent */
    public $packageSelect;

    /** @var ImageRepository @inject */
    public $imageRepository;

    /** @var RouteRepository @inject */
    public $routeRepository;


    /** @var array */
    private $adminPageControls = [];

    /** @var PageEntity */
    private $pageEntity;

    /** @var RouteEntity */
    private $routeEntity;

    /** @var RouteEntity[] */
    private $pagePackageRoutesEntity = [];

    /** @var Nette\Utils\Callback[] */
    public $onPageList= [];

    /** @var Nette\Utils\Callback[] event is called when/before edit page, can modify pageEntity and routeEntity */
    public $onPageEdit= [];

    /** @var Nette\Utils\Callback[] event is called from controls */
    public $onPageRedraw = [];


    /** @var IPackageControlFactory @inject */
    public $packageControlFactory;






    public function handleReload()
    {
        if ($this->isAjax()) {
            if ($this->pageEntity->getType() == 'static') {
                $this->redrawControl('staticPageContent');
                $this->payload->reload = true;
//                $this->redrawControl();

            } else {
                $this['dynamicPageControl']->redrawControl();
            }

        } else {
            $this->redirect('this');
        }


    }



    public function handleEditTranslate($id)
    {

        $this->flashMessage('Změněno', 'success');
        $this->ajaxRedirect();
    }


    public function actionTest($id)
    {

    }

    /**
     * @param $id
     * @todo experimental use only, not completed!!!
     */
    public function handleAddSubPage($id)
    {
        /** @var PageEntity $pageEntity */
        if (!$pageEntity = $this->pageFacade->getPageRepository()->find($id)) {
            $this->flashMessage('Page not found', 'danger');
            $this->redirect('default');
        }


        for ($i = 1; $i<=24; $i++)
        {
            $routeEntity = new RouteEntity($pageEntity, $this->translator);

            $params = ['id' => $i];
            $uri = ':' . ucfirst($pageEntity->getModule()) . ':' . ucfirst($pageEntity->getPresenter()) . ':' . $pageEntity->getAction();
            $url = Nette\Utils\Strings::webalize("{$pageEntity->getPresenter()}-{$pageEntity->getAction()}-{$i}");
            $title = ucfirst($pageEntity->getPresenter()) . ' - ' . $pageEntity->getAction();

    //        dump($params);
    //        dump($this->link($uri, $params));
    //        die();


            $routeEntity
                ->setName($this->translator->translate('unknown page'))
                ->setParams($params)
                ->setUrl($url)
                ->setUri($uri)
                ->setTitle($title);

            $this->entityFormMapper->getEntityManager()->persist($routeEntity);

            $routeEntity->mergeNewTranslations();
        }

        $this->entityFormMapper->getEntityManager()->flush();





//        dump($routeEntity);
//        dump($pageEntity);
//        die();





        $this->template->tabSelect = 'pageSettings';
        $this->ajaxRedirect();
    }


    public function actionEdit($id)
    {
        /** @var PageEntity $page */
        if (!$id || (!$page = $this->pageEntity = $this->pageFacade->getPageRepository()->find($id))) {
            $this->flashMessage('Page not found', 'danger');
            $this->redirect('default');
        }

        $routeEntity = $page->getMainRoute();

        $this->onPageEdit($page, $routeEntity, $this);

//        dump($routeEntity);

        /*
         * $page and route can modified after onPageEdit call
         */
        $page = $this->getPageEntity();
        $routeEntity = $this->getRouteEntity();

//        dump($routeEntity);
//        die();


        /*
         * not work yet,  delete?
         */
//        $this->onPageRedraw[] = [$this, 'handleReload'];
        $this->onPageRedraw[] = function () {
            if ($this->pageEntity->getType() == 'static') {
                $this->redrawControl('staticPageContent');

            } else {
                $this['dynamicPageControl']->redrawControl();
            }

        };





        /** @var Nette\Application\UI\Presenter $presenter */
//        $presenter = $this->context->getByType($page->getClass());

//        dump($presenter);
//        dump($layout = $presenter->findLayoutTemplateFile());
//        dump($presenter->formatLayoutTemplateFiles());
//        dump($presenter->formatTemplateFiles());
//        dump($page);
//
//        $fileLayout = file_get_contents($layout);
//        dump($fileLayout);


//        $crawler = HtmlPageCrawler::create($fileLayout);

//        dump($crawler);

//        $links = $crawler->filter('link');
//        dump($links);
//
//        foreach ($links as $link) {
//            $_link = HtmlPageCrawler::create($link);
//            dump($_link->saveHTML());
//        }
//
//
//        die();



//        $this->template->id = $id;



//        dump($result);
//        dump($e);
//        dump($page);
//        die();


//        dump($this->template->adminPageContentControls);
//        dump($this->template->adminPageControls);
//        die();

//        Debugger::barDump($this['dynamicPageControl']->page, 'page');

/*
        $route = $this->getPageEditRoute($page);
        dump($route);

        $q = $this->imageRepository->findBy(['route' => $route]);

        dump($q);
        die();
*/




//        dump($selectEditPage);
//        dump($result);
//        die();


    }


    private function debugLess()
    {
        Debugger::$maxLength = 150000;
        Debugger::$maxDepth = 7;
        $file = "/var/www/html/devrun-souteze/src/care/less/index.less";
        $fileVarr = "/var/www/html/devrun-souteze/src/care/bootstrap/variables.less";
        $root = '/care/less/';
        $options = array( 'compress'=>true );

        $parser = new \Less_Parser($options);
//        $lessParser = $parser->parseFile($fileVarr);


        /** @var \Less_Parser $lessParser */
        $lessParser = $parser->parseFile($file);
        $css = $parser->getCss();
        dump($css);

        $variables = $parser->getVariables();
        dump($variables);

        $parser->ModifyVars([
            'background-color' => '#afbf5f',
        ]);

        $variables = $parser->getVariables();
        dump($variables);


        $css = $parser->getCss();

        dump($css);


        dump($lessParser);

        $imported_files = $parser->allParsedFiles();
        dump($imported_files);


        dump($parser);
        die();



        $css = $parser->getCss();

        $parser->ModifyVars( array('font-size-base'=>'26px') );
        $parser->ModifyVars( array('@behoj'=>'16px') );

        $variables = $parser->getVariables();
        dump($variables);


        $q = $parser->findVarByName('@ahoj');
        dump($q);


//        dump($css);
//        die();




        $less = new \lessc();

        echo $less->compile(".block { padding: 3 + 4px }");

        $file = "/var/www/html/devrun-souteze/src/care/less/index.less";
        $dir = "/var/www/html/devrun-souteze/src/care/less";

//        $less->setImportDir($dir);
        $out = $less->compileFile($file);
        $parse = $less->allParsedFiles();

        dump($parse);


        $parser = new \lessc_parser($less);
//        $parser->writeComments = $this->preserveComments;

        dump($parser);



        die();

    }


    private function setPageAdministrationControls()
    {
        $page = $this->getPageEntity();
        $html = "<h2 class='text-danger text-center'>Prázdná stránka.</h2>";

        /*
         * static page sources
         */
        if ($routeEntity = $this->getRouteEntity()) {

            $params = $routeEntity->getParams();
            $params['generateDomain'] = false;
            if ($routeEntity->getPackage()) $params['package'] = $routeEntity->getPackage()->getId();

            try {
                $html = $this->pageFacade->getPageRepository()->getPageContentFromUrl($url = $this->link("//{$routeEntity->getUri()}", $params));
                $pageStyles      = $this->pageFacade->getPageRepository()->getPageStyles($url);
                $pageJavaScripts = $this->pageFacade->getPageRepository()->getPageJavaScripts($url);

            } catch (\Exception $exception) {
                $html = "<h2 class='text-danger text-center'>{$exception->getMessage()}</h2>";
                $pageStyles      = [];
                $pageJavaScripts = [];
            }

        } else {
            /*
             * dynamic pages sources
             */
            if ($routeEntity = $this->getRouteEntity()) {
                try {
                    $url             = $this->link("//{$routeEntity->getUri()}", $routeEntity->getParams());
                    $pageStyles      = $this->pageFacade->getPageRepository()->getPageStyles($url);
                    $pageJavaScripts = $this->pageFacade->getPageRepository()->getPageJavaScripts($url);

                } catch (\Exception $exception) {
                    $html = "<h2 class='text-danger text-center'>{$exception->getMessage()}</h2>";
                    $pageStyles      = [];
                    $pageJavaScripts = [];
                }
            }
        }

        $this->template->route             = $routeEntity;
        $this->template->package           = $routeEntity->getPackage();
        $this->template->pageHtml          = $html;
        $this->template->pageStyles        = $pageStyles;
        $this->template->pageJavaScripts   = $pageJavaScripts;
        $this->template->pagePackageRoutes = $this->getPagePackageRoutes();

        $settingControls = $this->administrationManager->getAdministrationComponentsByCategory('PageSettings');

        /*
         * pageSettingControls only in this page route
         */
        if ($pageRoute = $page->getMainRoute()) {
            $this->filterControlsByUri($settingControls, $pageRoute);

        } else {
            $this->filterControlsByUri($settingControls, $this->getRouteEntity());
        }

        /*
         * init controls [init attached]
         */
        foreach ($settingControls as $service => $settingControl) {
            $this['administrationItemControls'][$service];
        }


        $activityControls = $this->administrationManager->getAdministrationComponentsByCategory('Activity');

        /*
         * activityControls only in this page route
         */
        if ($pageRoute = $page->getMainRoute()) {
            $this->filterControlsByUri($activityControls, $pageRoute);

        } else {
            $this->filterControlsByUri($activityControls, $this->getRouteEntity());
        }

        $pageContentControls = $this->administrationManager->getAdministrationComponentsByCategory('PageContent');

        /*
         * init controls [init attached]
         */
        foreach ($pageContentControls as $service => $contentControl) {
            $this['administrationItemControls'][$service];
        }

        $pageTabControls = $this->administrationManager->getAdministrationComponentsByCategory('PageTab');




//        $this->adminPageControls = $page->mainRoute ? $this->getAdminPageControls($page->getMainRoute()->getUri()) : [];

        $this->template->page                     = $page;
        $this->template->adminSettingControls     = $settingControls;
        $this->template->adminPageControls        = $activityControls;
        $this->template->adminPageContentControls = $pageContentControls;
        $this->template->adminPageTabControls     = $pageTabControls;


    }


    public function renderEdit($id)
    {

        $this->setPageAdministrationControls();

//        $this->debugLess();
    }


    protected function createComponentDynamicPageControl()
    {
        $grid = new \Devrun\CmsModule\Controls\DataGrid();
        $grid->setTranslator($this->translator);
        $grid->setItemsPerPageList([1], false);

        $qb = $this->pageFacade->getPageRepository()->createQueryBuilder()
            ->from(RouteEntity::getClassName(), 'e')
            ->addSelect('e')
            ->addSelect('p')
            ->leftJoin('e.translations', 't')
            ->leftJoin('e.page', 'p')
            ->where('p = :page')->setParameter('page', $this->pageEntity);





//        = new Doctrine($qb, ['name' => 't.name', 'title' => 't.title', 'url' => 't.url'])

        $grid->setDataSource($qb);

        $grid->addColumnText('id', 'preview')
            ->setRenderer(function (RouteEntity $route) {
                $html   = $this->pageFacade->getPageRepository()->getPageContentFromUrl($url = $this->link("//{$route->getUri()}", $route->getParams()));
//                $return = (Nette\Utils\Html::el('div')->addAttributes(['class' => 'main-wrapper'])
//                    ->setHtml($html)
//                );

                $result = new Nette\Utils\Html();
                $result->setHtml($html);

//                Debugger::barDump($route);
//                $this->routeEntity = $route;


                return $result;
            });


        $grid->addFilterText('params', 'Parametry { json }');
        $grid->addFilterText('name', 'Název stránky (locale)');
        $grid->addFilterText('title', 'Title stránky (locale)', 't.title');
        $grid->addFilterText('url', 'Url (locale)');

        $grid->setOuterFilterRendering();
        $grid->setTemplateFile(__DIR__ . "/templates/Page/#datagrid_page.latte" );

        $p = $this;

        $grid->onRedraw[] = function () use ($p) {
            $p->redrawControl('pageContentControls');
            $p->redrawControl('pageActivityControls');
            $p->redrawControl('settingControls');
        };

        return $grid;
    }





    /**
     * filter array of admin components by uri
     * components only some page
     */
    private function filterControlsByUri(&$controls, RouteEntity $route)
    {
        foreach ($controls as $service => $control) {
            if (isset($control['uri']) && $control['uri'] != $route->getUri()) {
                unset($controls[$service]);
                continue;
            }
            if (isset($control['params']) && $control['params'] != $route->getParams()) {
                unset($controls[$service]);
            }
        }
    }


    /**
     * @return PageEntity  return edited page entity
     */
    public function getPageEntity()
    {
        return $this->pageEntity;
    }

    /**
     * @param PageEntity $pageEntity
     */
    public function setPageEntity($pageEntity)
    {
        $this->pageEntity = $pageEntity;
    }


    /**
     * @return RouteEntity|null   return edited route entity
     */
    public function getRouteEntity()
    {
        static $route;

        if (!$this->routeEntity) {
            if ($page = $this->pageEntity) {
                if ($page->getType() == 'static') {

                    $pagePackageRoutes = $this->getPagePackageRoutes();
                    if (count($pagePackageRoutes) == 1) {
                        $firstRoute = reset($pagePackageRoutes);
                        $this->setRouteEntity($firstRoute);
                    } elseif (count($pagePackageRoutes) > 1) {

                        if ($this->routeSelect) {

                            /*
                             * pokud neexistuje selectovaná routa, nastavíme první nalezenou
                             */
                            if (!isset($this->getPagePackageRoutes()[$this->routeSelect])) {
                                if ($pagePackageRoutes = $this->getPagePackageRoutes()) {
                                    $firstRoute = reset($pagePackageRoutes);
                                    $this->routeSelect = $firstRoute->getId();
                                }
                            }

                            $this->setRouteEntity($this->getPagePackageRoutes()[$this->routeSelect]);
                        } else {

                            /*
                             * routeSelect == null, zatím není selectovaná routa, podíváme se na balíčky
                             */
                            if ($this->package) {
                                foreach ($pagePackageRoutes as $pagePackageRoute) {
                                    if ($routePackage = $pagePackageRoute->getPackage()) {
                                        if ($routePackage->getId() == $this->package) {
                                            $this->routeSelect = $pagePackageRoute->getId();
                                            $this->setRouteEntity($this->getPagePackageRoutes()[$this->routeSelect]);
                                        }
                                    }
                                }

                            }
                        }
                    }

                    if (!$this->routeEntity) {
                        $this->setRouteEntity($page->getMainRoute());
                    }

                } elseif ($page->getType() == 'dynamic') {
                    $grid = $this['dynamicPageControl'];

                    $data = $grid->getFilterData();
                    $route = count($data) == 1 ? $data[0] : null;
                    $this->setRouteEntity($route);
//                    dump($route);
//                    die();
                }
            }
        }

/*        if (!$route) {
            $grid = $this['dynamicPageControl'];

            $data = $grid->getFilterData();
            $route = count($data) == 1 ? $data[0] : null;
        }*/

//        dump($this->routeEntity);

//        return $route;
        return $this->routeEntity;
    }

    /**
     * @param RouteEntity $routeEntity
     */
    public function setRouteEntity($routeEntity)
    {
        $this->routeEntity = $routeEntity;
    }


    /**
     * @return RouteEntity[]
     */
    public function getPagePackageRoutes()
    {
        if (!$this->pagePackageRoutesEntity) {
            $this->pagePackageRoutesEntity = $this->user->isAllowed('Cms:Page', 'editAllPackages')
                ? $this->routeRepository->findAssoc(['page' => $this->getPageEntity()], 'id')
                : $this->routeRepository->findAssoc(['page' => $this->getPageEntity(), 'package.user' => $this->user->getId()], 'id');
        }

        return $this->pagePackageRoutesEntity;
    }




    /**
     * @return \Devrun\CmsModule\Controls\PackageControl
     */
    protected function createComponentPackage()
    {
        $control = $this->packageControlFactory->create();
        $control
            ->setRouteList($this->getPagePackageRoutes())
            ->setSelectRouteByPackage($this->routeSelect)
            ->onSuccess[] = function ($values) {

            $this->routeSelect = $values->route;
            $this->routeEntity = null;

            $selectRouteEntity = $this->getPagePackageRoutes()[$this->routeSelect];
            $this->package = $selectRouteEntity->getPackage()->getId();

            $this->redirect('this');
//            $this->ajaxRedirect('this', null, ['content', 'link', 'styles']);
        };

        return $control;
    }



}
