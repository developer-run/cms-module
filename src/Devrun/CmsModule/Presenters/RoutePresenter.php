<?php


namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Nette\Utils\Strings;
use Tracy\Debugger;

/**
 * Class RoutePresenter
 * @package Devrun\CmsModule\Presenters
 *
 * @secured
 */
class RoutePresenter extends AdminPresenter
{

    /**
     * @secured(privilege="show")
     */
    public function renderDefault()
    {


    }


    public function handleAddPage($id)
    {
        $publicPages = $this->getPublicPages();

        if (!isset($publicPages[$id])) {
            die("not set");
        }

        $publicPage = $publicPages[$id];

//        dump($id);
//        dump($publicPages);


        $uri = ':' . ucfirst($publicPage['module']) . ':' . $publicPage['presenter'] . ':' . $publicPage['action'];
        $url = Strings::webalize($publicPage['presenter'] . '-' . $publicPage['action']);
        $title = ucfirst($publicPage['presenter']) . ' - ' . $publicPage['action'];

//        dump($uri);
//        dump($url);
//        dump($title);

        $urlRoutes = $this->pageFacade->getPageRepository()
            ->createQueryBuilder()
            ->from(RouteTranslationEntity::class, 'e')
            ->select('e.url')
            ->getQuery()
            ->getResult();

//        dump($urlRoutes);

        $inc = 1;
        $checkUrl = $url;
        while (in_array($checkUrl, $urlRoutes)) {
            $checkUrl = $url . "-" . $inc++;
        }

        $urlRoutes[] = $url = $checkUrl;

        $pageEntity = (new PageEntity($publicPage['module'], $publicPage['presenter'], $publicPage['action'], $this->translator))
            ->setClass($publicPage['class'])
            ->setFile($publicPage['template'])
            ->setType($publicPage['pageType']);

//        dump($pageEntity);

        $routeEntity = new RouteEntity($pageEntity, $this->translator);
        $routeEntity
            ->setName($this->translator->translate('unknown page'))
            ->setUrl($url)
            ->setUri($uri)
            ->setParams([])
            ->setTitle($title);

//        dump($routeEntity);


        $this->pageFacade->getPageRepository()->getEntityManager()->persist($pageEntity)->persist($routeEntity);
        $pageEntity->mergeNewTranslations();
        $routeEntity->mergeNewTranslations();

        $this->pageFacade->getPageRepository()->getEntityManager()->flush();


        $this->ajaxRedirect();

//        die();

    }


    /**
     * @param $name
     * @return \Devrun\CmsModule\Controls\DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    protected function createComponentPagesGrid($name)
    {
        $grid = $this->createGrid($name);

        /** @var PageEntity[] $inPages */
        $inPages  = $this->pageFacade->getPageRepository()->findAll();

//        dump($inPages);

        $presenters = $this->getPublicPages();
//        dump($presenters);

        $grid->setDataSource($presenters);


        $grid->addColumnText('id', 'Klíč')
             ->setSortable()
             ->setFilterText();


        $grid->addAction('addPage', 'addPage', 'addPage!')
             ->setIcon('calendar-plus-o')
             ->setClass('btn btn-xs btn-primary _ajax');


        return $grid;
    }


    private function getPublicPages()
    {
        $staticPages = $this->moduleFacade->findUnSyncedPublicStaticPages(true);

        Debugger::$maxDepth = 4;
//        dump($staticPages);

        $presenters = [];
        foreach ($staticPages as $module => $staticPage) {
            foreach ($staticPage as $presenterName => $presenterPages) {
                foreach ($presenterPages as $page => $presenterPage) {
                    $presenters["$module:$presenterName:$page"] = [
                        'id'        => "$module:$presenterName:$page",
                        'module'    => $module,
                        'presenter' => $presenterName,
                        'action'    => $page,
                        'class'     => $presenterPage['class'],
                        'template'  => $presenterPage['template'],
                        'pageType'  => 'static',
                    ];
                }
            }
        }

        return $presenters;
    }


}