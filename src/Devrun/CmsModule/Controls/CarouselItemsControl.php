<?php
/**
 * This file is part of cms
 * Copyright (c) 2019
 *
 * @file    CarouselItemsControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\Application\UI\Presenter\TImgStoragePipe;
use Kdyby\Doctrine\QueryBuilder;

interface ICarouselItemsControlFactory
{
    /** @return CarouselItemsControl */
    function create();
}

class CarouselItemsControl extends Control
{
    use TImgStoragePipe;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var string */
    private $templateFile;


    public function render($params = [])
    {
        $criteria = $params['criteria'] ?? [];
        $limit = $params['limit'] ?? null;

        $items = $this->queryBuilder
            ->whereCriteria($criteria)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $template = $this->getTemplate();
        $template->items = $items;
        if ($this->templateFile) {
            $template->setFile($this->templateFile);
        }
        $template->render();
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @return CarouselItemsControl
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): CarouselItemsControl
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }


    /**
     * @param string $templateFile
     *
     * @return CarouselItemsControl
     */
    public function setTemplateFile(string $templateFile): CarouselItemsControl
    {
        $this->templateFile = $templateFile;
        return $this;
    }





}