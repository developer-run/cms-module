<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    DashboardPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Nette\Utils\Html;
use Tracy\Debugger;

class DashboardPresenter extends AdminPresenter
{

    public function renderDefault()
    {

        $repository = $this->pageFacade->getPageRepository();

//        $pages = $repository->
        $root = $repository->getRootNodes();
//        dump($root);

        $pxHomepage = $repository->findOneBy(['name' => 'pexeso:homepage:default']);

        $pexeso = $repository->getPath($pxHomepage);

//        dump($pexeso);



        $options = array(
            'html'                => true,
            'decorate'            => true,
            'rootOpen'            => function($node) use ($repository) {
                Debugger::barDump("rootOpen");
                Debugger::barDump($node);

//                die();
                return '<ul class="treeview-menu" _style="display: block;">';
            },
            'rootClose'            => function($node) {
                Debugger::barDump("rootClose");
                Debugger::barDump($node);
                return "</ul>";
            },
            'representationField' => 'name',
            'childOpen' => function ($node) {
                Debugger::barDump("childOpen");
                Debugger::barDump($node);
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
                Debugger::barDump("childClose");
                Debugger::barDump($node);

                return "</li>";

//                return '<ul class="treeview-menu" style="_display: block;">';
//                die();

            },
            'nodeDecorator' => function ($node) {
                Debugger::barDump("nodeDecorator");
                Debugger::barDump($node);
//                die();

                $i = Html::el('i');
                $i->setAttribute('class', $node['__children'] ? 'fa fa-circle-o-notch text-aqua' : 'fa fa-circle-thin');

                $span = Html::el('span');
                $span->setAttribute('class', 'pull-right-container')
                    ->addHtml(Html::el('i')->setAttribute('class', 'fa fa-angle-left pull-right'));

                $note = sprintf("<a class='fa fa-circle-o-notch'></a>");
                $a = Html::el('a')->addAttributes(['class' => '_ajax'])->href($this->getPresenter()->link(":Cms:Page:edit", ['id' => $node['id']]));
//                $a->addHtml($i . $node['translations']['cs']['title']);
                $a->addHtml($i . $node['name']);

                if ($node['__children']) $a->addHtml($span);
                return $a;
            },
        );

        $chierarchy = $repository->childrenHierarchy(null, false, $options, true);


        echo $chierarchy;
        dump($chierarchy);









//        die();






    }

}