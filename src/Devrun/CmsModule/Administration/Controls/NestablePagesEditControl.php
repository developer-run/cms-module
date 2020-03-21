<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    NestablePagesEditControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Controls\INavigationPageControlFactory;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Repositories\PageRepository;
use Kdyby\Translation\ITranslator;
use Nette\Security\User;
use Nette\Utils\Html;
use Tracy\Debugger;

interface INestablePagesEditControl
{
    /** @return NestablePagesEditControl */
    function create();
}

class NestablePagesEditControl extends Control
{

    /** @var bool @persistent */
    public $toggle = true;

    /** @var INavigationPageControlFactory @inject */
    public $navigationPageControlFactory;

    /** @var INestablePagesControl @inject */
    public $pagesNestableControl;

    /** @var PageRepository @inject */
    public $pageRepository;

    /** @var ITranslator @inject */
    public $translator;
    
    /** @var User @inject */
    public $user;



    public function handlePageNestable()
    {
        $this->toggle = !$this->toggle;
        $this->redrawControl();
    }



    public function render()
    {
        $template         = $this->getTemplate();
        $template->toggle = $this->toggle;
        $template->render();
    }



    protected function createComponentPagesNestableControl()
    {
        $control = $this->pagesNestableControl->create();
        $control->setData($this->pageRepository->childrenHierarchy());
        return $control;
    }


    /**
     * @return \Devrun\CmsModule\Controls\NavigationPageControl
     */
    protected function createComponentNavigationPageControl()
    {
        $control = $this->navigationPageControlFactory->create();

        $options = array(
            'html'                => true,
            'decorate'            => true,
            'rootOpen'            => function($node) {
//                Debugger::barDump("rootOpen");
//                Debugger::barDump($node);
//                die();
                return '<ul class="treeview-menu" style="display: block;">';
            },
            'rootClose'            => function($node) {
//                Debugger::barDump($node);
//                Debugger::barDump("rootClose");
                return "</ul>";
            },
            'representationField' => 'name',
            'childOpen' => function ($node) {
//                Debugger::barDump("childOpen");
//                Debugger::barDump($node);
                $class = '';
                if ($node['__children']) {
                    $class .= 'treeview menu-open ';
                }
//                die();
                $class .= $this->getPresenter()->isLinkCurrent(":Cms:Page:edit", ['id' => $node['id']]) ? 'active' : '';
                $class = trim($class);

                return $class ? "<li class='$class'>" : "<li>";
            },
            'childClose' => function($node) {
//                Debugger::barDump("childClose");


//                Debugger::barDump($node);

                return "</li>";

//                return '<ul class="treeview-menu" style="_display: block;">';
//                die();

            },
            'nodeDecorator' => function ($node) {

//                Debugger::barDump("nodeDecorator");
//                dump($node);
//                die();

                $i = Html::el('i');
                $i->setAttribute('class', $node['__children'] ? 'fa fa-circle-o-notch text-aqua' : 'fa fa-circle-thin');

                $span = Html::el('span');
                $span->setAttribute('class', 'pull-right-container')
                    ->addHtml(Html::el('i')->setAttribute('class', 'fa fa-angle-left pull-right'));

                $note = sprintf("<a class='fa fa-circle-o-notch'></a>");
                $a = Html::el('a')->addAttributes(['class' => '_ajax'])->href($this->getPresenter()->link(":Cms:Page:edit", ['id' => $node['id']]));
                $a->addHtml($i . $node['translations']['cs']['title']);

                if ($node['__children']) $a->addHtml($span);
                return $a;
            },
        );




        $query = $this->pageRepository->createQueryBuilder('a')
//            ->addSelect('r')
            ->addSelect('t')
//            ->addSelect('partial r.{id}')
//            ->addSelect('partial t.{id,title}')
//            ->join('a.mainRoute', 'r')
            ->leftJoin('a.translations', 't')
//            ->andWhere('t.locale = :locale')->setParameter('locale', 'cs')
            ->addOrderBy('a.root')
            ->addOrderBy('a.lft');

        /** @var PageEntity $one */
//        $one = $query->getOneOrNullResult();

//        dump($one);

//        dump($one->getMainRoute()->getTitle());

        if (!$this->user->isAllowed('Cms:Page', 'editAllPackages')) {
            $query->leftJoin('a.routes', 'r');
            $query->leftJoin('r.package', 'p');
            $query->andWhere('a.module = p.module');
            $query->andWhere('p.user = :user')->setParameter('user', $this->user->getId());
        }

        if (!$this->user->isAllowed('Cms:Page', 'viewUnpublishedPages')) {
            $query->andWhere('a.published = TRUE');
        }

        $query = $query->getQuery();

//        Debugger::barDump($query->getResult());
//        dump($query->getArrayResult());
//        die();



        $control
            ->setQuery($query)
            ->setOptions($options);

        return $control;
    }




}