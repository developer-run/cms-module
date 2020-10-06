<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    NavigationPageTreePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Repositories\PageRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Forms\Container;
use Nette\Utils\Html;
use Nette\Utils\Validators;
use Tracy\Debugger;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class NavigationTreePagePresenter extends AdminPresenter
{

    /** @var PageRepository @inject */
    public $pageRepository;

    /** @var EntityManager @inject */
    public $em;


    private function getUpPositions($previousId, $nextId, array $prevSiblings = [], array $nextSiblings = [])
    {
        $count = null;

        if ($prevSiblings) {
            $currentPos = count($prevSiblings) - 1;
            $siblings = array_merge($prevSiblings, $nextSiblings);

            if ($previousId) {
                for ($i = $currentPos, $j = $currentPos + 1, $index = 0; $i >= 0; $i--, $j--, $index++) {
                    $currSibling = $siblings[$i];
                    $nextSibling = $j < count($siblings) ? $siblings[$j] : null;

                    if ($currSibling instanceof PageEntity) {
                        if ($previousId == $currSibling->getId()) {
                            if ($nextSibling instanceof PageEntity) {
                                if ($nextId == $nextSibling->getId()) {
                                    $count = $index;
                                    break;
                                }
                            }
                        }
                    }
                }

            } else {
                $nextSibling = $siblings[0];
                if ($nextSibling instanceof PageEntity) {
                    if ($nextId == $nextSibling->getId()) {
                        $count = true;
                    }
                }
            }
        }

        return $count;
    }


    private function getDownPositions($previousId, $nextId, array $prevSiblings = [], array $nextSiblings = [])
    {
        $count = null;

        if ($nextSiblings) {
            $currentPos = count($prevSiblings);
            $siblings = array_merge($prevSiblings, $nextSiblings);

            if ($nextId) {
                for ($i = $currentPos, $j = $currentPos - 1, $index = 0; $i < count($siblings); $i++, $j++, $index++) {
                    $currSibling = $siblings[$i];
                    $prevSibling = $j > 0 ? $siblings[$j] : null;

                    if ($currSibling instanceof PageEntity) {
                        if ($nextId == $currSibling->getId()) {
                            if ($prevSibling instanceof PageEntity) {
                                if ($previousId == $prevSibling->getId()) {
                                    $count = $index;
                                    break;
                                }
                            }
                        }
                    }
                }

            } else {
                $prevSibling = end($siblings);
                if ($prevSibling instanceof PageEntity) {
                    if ($previousId == $prevSibling->getId()) {
                        $count = true;
                    }
                }
            }
        }

        return $count;
    }



    public function handleSort($item_id, $prev_id, $next_id, $parent_id)
    {
        /** @var PageEntity $item */
        $item     = $this->pageRepository->find($item_id);

        // pokud se jedná o root
        if (null == $item->getParent()) {
            $itemPages = [];
            $_itemPages = $this->pageRepository->createQueryBuilder('e')
                ->select('e.id, e.rootPosition')
                ->where('e.root = :root')->setParameter('root', $item->getRoot())
                ->orderBy('e.rootPosition')
                ->getQuery()
                ->getResult();

            foreach ($_itemPages as $itemPage) {
                $itemPages[$itemPage['id']] = $itemPage;
            }

            $_pages = $this->pageRepository->createQueryBuilder('e')
                ->select('r.id, e.rootPosition')
                ->join('e.root', 'r')
                ->groupBy('e.root')
                ->where('e.root != :root')->setParameter('root', $item->getRoot())
                ->orderBy('e.rootPosition')
                ->getQuery()
                ->getResult();

            if ($next_id) {
                foreach ($_pages as $index => $page) {
                    if ($page['id'] == $next_id) {
                        array_splice($_pages, $index, 0 , $itemPages);
                    }
                }

            } else {
                array_splice($_pages, count($_pages), 0 , $itemPages);
            }

            /** @var PageEntity[] $pageEntities */
            $pageEntities = $this->pageRepository->findBy([], ['root' => 'ASC']);

            foreach ($_pages as $index => $page) {
                foreach ($pageEntities as $pageEntity) {
                    if ($pageEntity->getRoot()->getId() == $page['id']) {
                        $pageEntity->setRootPosition($index + 1);
                    }
                }
            }

            // nejedná se o root položku
        } else {

            // položka je posouvána ve stejném uzlu
            if ($parent_id == $item->getParent()->getId()) {
                $nextSiblings = $this->pageRepository->getNextSiblings($item);
                $prevSiblings = $this->pageRepository->getPrevSiblings($item);

                $moveUpPositions = $this->getUpPositions($prev_id, $next_id, $prevSiblings, $nextSiblings);
                $moveDownPositions = $this->getDownPositions($prev_id, $next_id, $prevSiblings, $nextSiblings);

                if ($moveUpPositions !== null) {
                    $this->pageRepository->moveUp($item, $moveUpPositions);

                } elseif ($moveDownPositions !== null) {
                    $this->pageRepository->moveDown($item, $moveDownPositions);
                }

            } else {
                // přesouvá se do jinéh uzlu, musí být ale splněna podmínka o tree root uzlu
                /** @var PageEntity $parent */
                if ($parent = $this->pageRepository->find($parent_id)) {
                    if ($parent->getRoot() == $item->getRoot()) {
                        $item->setParent($parent);

                        $nextSiblings = $this->pageRepository->getNextSiblings($item);
                        $prevSiblings = $this->pageRepository->getPrevSiblings($item);

                        $moveUpPositions = $this->getUpPositions($prev_id, $next_id, $prevSiblings, $nextSiblings);
                        $moveDownPositions = $this->getDownPositions($prev_id, $next_id, $prevSiblings, $nextSiblings);

                        if ($moveUpPositions !== null) {
                            $this->pageRepository->moveUp($item, $moveUpPositions);

                        } elseif ($moveDownPositions !== null) {
                            $this->pageRepository->moveDown($item, $moveDownPositions);
                        }
                    }
                }
            }
        }


//        $verify = $this->pageRepository->verify();
//        Debugger::barDump($verify, 'verify');

//        if ($verify) {
//            $this->pageRepository->recover();
//        }

        $this->em->persist($item)->flush();
        $this->ajaxRedirect();
    }


    public function handleRemoveCategory($id)
    {
        /** @var PageEntity $entity */
        if ($entity = $this->pageRepository->find($id)) {
            $entity->clearCategoryName();
            $entity->mergeNewTranslations();
            $em = $this->pageRepository->getEntityManager();
            $em->persist($entity);
            $em->flush();
            $this->flashMessage("Uživatel {$entity->getTitle()} upravena", 'success');
            $this->ajaxRedirect('this', ['pageGridControl', 'pageTreeGridControl'], 'flash');
        }

    }


    protected function createComponentPageTreeGridControl($name)
    {
        $grid = $this->createGrid($name);

        $query = $this->pageRepository->createQueryBuilder('a')
//            ->addSelect('t')
//            ->leftJoin('a.translations', 't')
            ->andWhere('a.lvl = :level')->setParameter('level', 0)
            ->addOrderBy('a.rootPosition')
            ->addOrderBy('a.lft');



        $grid->setDataSource($query);

        $grid->setTreeView(function ($id) {
            $entity = $this->pageRepository->find($id);
            $result = $this->pageRepository->childrenQueryBuilder($entity, true)
                ->getQuery()
                ->getResult();

            return $result;

        }, function (PageEntity $pageEntity) {
            return $this->pageRepository->childCount($pageEntity) > 0;
        });

        $grid->addColumnText('title', 'Name')
            ->setAlign('text-left')
            ->setSortable()
            ->setRenderer(function (PageEntity $pageEntity) {
                return $pageEntity->isCategoryName()
                    ? "({$pageEntity->getCategoryName()}) " . $pageEntity->getTitle()
                    : $pageEntity->getTitle();
            });



        $grid->addColumnText('categoryName', 'Kategorie')
            ->setAlign('text-left')
            ->setSortable()
            ->setRenderer(function (PageEntity $pageEntity) {
                $html = Html::el("p")->setText($pageEntity->getCategoryName());
                if ($pageEntity->isCategoryName()) {
                    $html->addAttributes(['class' => 'text-primary']);
                }

                return $html;
            });

        $grid->setSortableHandler('sort!');
        $grid->setSortable();

        return $grid;
    }


    /**
     * @param $name
     * @return \Devrun\CmsModule\Controls\DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    protected function createComponentPageGridControl($name)
    {
        $grid = $this->createGrid($name);

        $query = $this->pageRepository->createQueryBuilder('a')
            ->addSelect('t')
            ->leftJoin('a.translations', 't')
            ->addOrderBy('a.rootPosition')
            ->addOrderBy('a.lft');

        $grid->setDataSource($query);

        $grid->addColumnText('module', 'Module')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('title', 'Name')
            ->setSortable()
            ->setRenderer(function (PageEntity $pageEntity) {
                $html = Html::el("p")->setText($pageEntity->getTitle());
                if ($pageEntity->isCategoryName()) {
                    $html->addAttributes(['class' => 'text-primary']);
                }

                return $html;
            })
            ->setEditableCallback(function ($id, $value) use ($grid) {
                if (Validators::is($value, $validate = 'string:3..32')) {

                    /** @var PageEntity $entity */
                    if ($entity = $this->pageRepository->find($id)) {
                        $entity->setTitle($value);
                        $entity->mergeNewTranslations();
                        $em = $this->pageRepository->getEntityManager();
                        $em->persist($entity);
                        $em->flush();
                        $this->flashMessage("Uživatel {$entity->getTitle()} upravena", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }
                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }
            })
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.title LIKE :title')->setParameter('title', "%$value%");
            });


        $grid->addColumnText('categoryName', 'Kategorie')
            ->setSortable()
            ->setRenderer(function (PageEntity $pageEntity) {
                $html = Html::el("p")->setText($pageEntity->getCategoryName());
                if ($pageEntity->isCategoryName()) {
                    $html->addAttributes(['class' => 'text-primary']);
                }

                return $html;
            })
            ->setEditableCallback(function ($id, $value) use ($grid) {
                if (Validators::is($value, $validate = 'string:3..32')) {

                    /** @var PageEntity $entity */
                    if ($entity = $this->pageRepository->find($id)) {
                        $entity->setCategoryName($value);
                        $entity->setPublished(false);
                        $em = $this->pageRepository->getEntityManager();
                        $em->persist($entity);
                        $entity->mergeNewTranslations();
                        $em->flush();
                        $this->flashMessage("Uživatel {$entity->getTitle()} upravena", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }

                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }
            })
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.categoryName LIKE :categoryName')->setParameter('categoryName', "%$value%");
            });

        $grid->addAction('removeCategory', 'remove Category', 'removeCategory!')
            ->setIcon('eye fa-2x')
            ->setClass('ajax btn btn-xs btn-danger')
            ->setConfirmation(new StringConfirmation('Opravdu chcete smazat kategorii [id: %s]?', 'categoryName'));

        return $grid;
    }


}