<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    TreePagesControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Repositories\PageRepository;
use Doctrine\ORM\Query;
use Flame\Application\UI\Control;
use Nette\Security\User;

interface ITreePagesControlFactory
{
    /** @return TreePagesControl */
    function create();
}

class TreePagesControl extends Control
{

    /** @var PageRepository @inject */
    public $pageRepository;

    /** @var User @inject */
    public $user;

    /** @var PageEntity[] */
    private $pages = [];


    /** @var bool DI setter */
    private $firstLevelVisible = false;


    public function render($params = array())
    {
        $template = $this->getTemplate();

        $query = $this->getQuery();
        $this->setPages($query);

        $template->firstLevelVisible = $this->firstLevelVisible;
        $template->nodes = $this->getNodes();
        $template->render();
    }


    private function setPages(Query $query)
    {
        $results = $query->getResult();

        $pages = [];
        /** @var PageEntity[] $results */
        foreach ($results as $result) {
            $pages[$result->getId()] = $result;
        }

        $this->pages = $pages;
    }


    public function getPage($id)
    {
        return isset($this->pages[$id]) ? $this->pages[$id] : null;
    }


    public function isChildrenLinkCurrent($node)
    {
        $result    = false;
        $presenter = $this->getPresenter();

        if ($presenter->isLinkCurrent(':Cms:Page:edit', ['id' => $node['id']])) {
            $result = true;
            return $result;
        }

        foreach ($node['__children'] as $child) {
            if ($presenter->isLinkCurrent(':Cms:Page:edit', ['id' => $child['id']])) {
                $result = true;
                break;
            }
        }

        return $result;
    }


    /**
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    private function getQuery()
    {
        $query = $this->pageRepository->createQueryBuilder('a')
            ->addSelect('t')
            ->leftJoin('a.translations', 't')
            ->addOrderBy('a.rootPosition')
            ->addOrderBy('a.lft');

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
        return $query;
    }


    private function getNodes()
    {
        /** @var PageEntity[] $pages */
        $pages      = $this->pages;
        $options    = array('decorate' => false);
        $arrayPages = [];

        foreach ($pages as $page) {
            $arrayPages[] = $page->toArray();
        }

        return $this->pageRepository->buildTree($arrayPages, $options);
    }

    /**
     * @param bool $firstLevelVisible
     */
    public function setFirstLevelVisible(bool $firstLevelVisible)
    {
        $this->firstLevelVisible = $firstLevelVisible;
    }



}