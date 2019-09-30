<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    NavigationPageControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\CmsModule\Repositories\PageRepository;
use Doctrine\ORM\Query;
use Flame\Application\UI\Control;
use Nette\FileNotFoundException;
use Tracy\Debugger;

interface INavigationPageControlFactory
{
    /** @return NavigationPageControl */
    function create();
}

class NavigationPageControl extends Control
{
    const CUSTOM_TEMPLATE_NAME = "NavigationCustomPageControl.latte";

    /** @var PageRepository @inject */
    public $pageRepository;

    /** @var array */
    protected $options = array();

    /** @var Query */
    protected $query = NULL;

    /** @var string */
    private $customTemplateFile;


    public function render()
    {
        $template = $this->getTemplate();

        if (!$query = $this->query) {
            $query = $this->createQuery();
        }

        if (!$options = $this->options) {
            $options = $this->createBootstrapMultipleOptions();
        }

        $template->navigation = $this->pageRepository->buildTree($query->getArrayResult(), $options);
        $template->render();
    }


    public function renderCustom()
    {
        $template = $this->getTemplate();
        $template->setFile($this->getCustomTemplateFile());

        if (!$query = $this->query) {
            $query = $this->createQuery();
        }

        $template->navigationTree = $this->pageRepository->buildTreeArray($query->getArrayResult());
        $template->render();
    }


    /**
     * @return Query
     */
    protected function createQuery()
    {
        $query = $this->pageRepository->createQueryBuilder('a')
            ->where('a.published = true')
            ->addOrderBy('a.root')
            ->addOrderBy('a.lft')
            ->getQuery();

        return $query;
    }


    /**
     * setter Query
     *
     * @param Query $query
     *
     * @return $this
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
        return $this;
    }


    /**
     * @return array
     */
    protected function createBootstrapOptions()
    {
        $iterate = 1;
        $options = array(
            'decorate'            => true,
            'rootOpen'            => function () use (&$iterate) {
                return $iterate++ > 1 ? '<ul class="dropdown-menu">' : '<ul class="nav navbar-nav">';
            },
            'representationField' => 'name',
            'nodeDecorator'       => function ($node) {
                if ($node['__children']) {
                    return sprintf("<a href=\"%s\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\">%s <span class=\"caret\"></span>", $this->getPresenter()->link($node['uri']), $node['title']);

                } else {
                    return '<a href="' . $this->getPresenter()->link($node['uri']) . '">' . $node['title'] . '</a>';
                }
            },
            'html'                => true
        );

        return $options;
    }


    /**
     * @return array
     */
    protected function createBootstrapMultipleOptions()
    {
        $iterate = 0;
        $options = array(
            'html'                => true,
            'decorate'            => true,
            'representationField' => 'name',
            'rootOpen'            => function () use (&$iterate) {
                $iterate++;
                if ($iterate == 1) return '<ul class="nav navbar-nav">';
                elseif ($iterate == 2) return '<ul class="dropdown-menu multi-level" role="menu" aria-labelledby="dropdownMenu">';
                else return '<ul class="dropdown-menu">';
            },
            'childOpen'           => function ($node) {
                return $node['__children'] ? '<li class="dropdown-submenu">' : '<li>';
            },
            'nodeDecorator'       => function ($node) {

//                return '<a href="' . $this->getPresenter()->link($node['mainRoute']['uri']) . '">' . $node['title'] . '</a>';
            },
        );

        return $options;
    }


    /**
     * setter options
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }


    /**
     * setter custom template
     *
     * @param string $customTemplateFile
     *
     * @return $this
     */
    public function setCustomTemplateFile($customTemplateFile)
    {
        if (!file_exists($this->customTemplateFile = $customTemplateFile)) {
            throw new FileNotFoundException($customTemplateFile);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomTemplateFile()
    {
        if (!$this->customTemplateFile) {
            if (!file_exists($customTemplateFile = $this->customTemplateFile = __DIR__ . DIRECTORY_SEPARATOR . self::CUSTOM_TEMPLATE_NAME)) {
                throw new FileNotFoundException($customTemplateFile);
            }

        }

        return $this->customTemplateFile;
    }


}