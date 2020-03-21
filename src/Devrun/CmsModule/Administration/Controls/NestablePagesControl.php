<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    NestablePagesControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Repositories\PageRepository;
use Nette\Http\Request;

interface INestablePagesControl
{
    /** @return NestablePagesControl */
    function create();
}

class NestablePagesControl extends Control
{

    /** @var Request @inject */
    public $request;

    /** @var PageRepository @inject */
    public $pageRepository;


    private $data = [];


    public function render()
    {
        $template       = $this->getTemplate();
        $template->data = $this->data;

        $template->render();

    }


    private function updateParentHierarchy(array $nestedData, $parentEntity = null)
    {
        foreach ($nestedData as $data) {

            /** @var PageEntity $entity */
            $entity = $this->pageRepository->find($data['id']);

            if ($parentEntity) {
                $entity->setParent($parentEntity);
                $this->pageRepository->getEntityManager()->persist($entity);

            } else {
                $entity->setParent(null);
                $this->pageRepository->getEntityManager()->persist($entity);
            }

            if (isset($data['children'])) {
                $this->updateParentHierarchy($data['children'], $entity);
            }
        }
    }


    public function handlePagesNested()
    {
        $nestedData = $this->request->getQuery('nestedData');
        $nestedData = json_decode($nestedData, true);

        $this->updateParentHierarchy($nestedData);
        $this->pageRepository->recover();
        $this->pageRepository->getEntityManager()->flush();

        $this->flashMessage('updatováno');

        if ($this->presenter->isAjax()) {
            $this->redrawControl();
            $this->presenter->redrawControl();

        } else $this->redirect('this');
    }


    private function setRecursiveData(array $arrayHierarchy)
    {
        $jsonHierarchy = [];

        foreach ($arrayHierarchy as $item) {
            $add = [
                'id'      => $item['id'],
                'content' => $item['title'],
            ];

            if (!empty($item['__children'])) {
                $result          = $this->setRecursiveData($item['__children']);
                $add['children'] = $result;
            }

            $jsonHierarchy[] = $add;
        }

        return $jsonHierarchy;
    }


    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $jsonHierarchy = $this->setRecursiveData($data);
        $jsonHierarchy = json_encode($jsonHierarchy);
        $this->data    = $jsonHierarchy;
        return $this;
    }


}