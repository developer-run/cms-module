<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    PageEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;

use Devrun\DoctrineModule\Entities\Attributes\Translatable;
use Devrun\DoctrineModule\Entities\NestedEntityTrait;
use Devrun\DoctrineModule\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\Translator;

/**
 * Class PageEntity
 * @Gedmo\Tree(type="nested")
 * _@_ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\PageRepository")
 * @ORM\Table(name="page", indexes={
 *     @ORM\Index(name="class_idx", columns={"class"}),
 *     @ORM\Index(name="page_module_idx", columns={"module"}),
 *     @ORM\Index(name="presenter_idx", columns={"presenter"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(
 *    name="page_name_idx", columns={"name"},
 * )})
 *
 * @package Devrun\CmsModule\Entities
 * @method getPresenter()
 * @method getAction()
 * @method getClass()
 * @method PageTranslationEntity translate($lang = '', $fallbackToDefault = true)
 */
class PageEntity
{
    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use NestedEntityTrait;
    use Translatable;

    /** @var Translator */
    private $translator;

    /**
     * @var ArrayCollection|RouteEntity[]
     * _@_ORM\Cache("NONSTRICT_READ_WRITE")
     * @ORM\OneToMany(targetEntity="RouteEntity", mappedBy="page", cascade={"persist", "remove", "detach"})
     */
    protected $routes;

    /**
     * @var RouteEntity
     * @ORM\ManyToOne(targetEntity="RouteEntity", cascade={"persist", "remove", "detach"})
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $mainRoute;

    /**
     * var PageEntity
     *
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="PageEntity")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="PageEntity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var PageEntity[]
     * _@_ORM\Cache("NONSTRICT_READ_WRITE")
     * @ORM\OneToMany(targetEntity="PageEntity", mappedBy="parent", cascade={"persist"})
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;


    /**
     * @var PageEntity
     * @ORM\ManyToOne(targetEntity="PageEntity", inversedBy="next", fetch="LAZY")  # ManyToOne is hack for prevent '1062 Duplicate entry update'
     */
    protected $previous;

    /**
     * @var PageEntity
     * @ORM\OneToMany(targetEntity="PageEntity", mappedBy="previous", fetch="LAZY")
     */
    protected $next;

    /** @ORM\Column(type="string", length=64, options={"default": "static"}) */
    protected $type = 'static';

    /** @ORM\Column(type="integer", name="tree_root_position") */
    protected $rootPosition = 1;





    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $published = true;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    protected $name;

    /**
     * @var string
     * @ORM\ManyToOne(targetEntity="ModuleEntity", inversedBy="pages")
     * @ORM\JoinColumn(name="module", onDelete="CASCADE", nullable=false)
     */
    protected $module;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $presenter;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $action;


    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    protected $class;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $file;

    /**
     * PageEntity constructor.
     *
     * @param string $module
     * @param string $presenter
     * @param string $action
     */
    public function __construct($module, $presenter, $action, ITranslator $translator)
    {
        $this->module    = strtolower($module);
        $this->presenter = strtolower($presenter);
        $this->action    = strtolower($action);
        $this->name      = "$this->module:$this->presenter:$this->action";
        $this->routes    = new ArrayCollection();

        $this->translator = $translator;

        $this->setDefaultLocale($this->translator->getDefaultLocale());
        $this->setCurrentLocale($this->translator->getLocale());

        $this->setTitle($this->name);
    }


    /***********************************************************
     * setters
     **********************************************************/

    /**
     * @param string $class
     *
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param string $file
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $presenter
     * @return $this
     */
    public function setPresenter($presenter)
    {
        $this->presenter = $presenter;
        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /*
     * languages
     */


    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->translate($this->currentLocale, false)->setTitle($title);
        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->translate($this->currentLocale, false)->setDescription($description);
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->translate()->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->translate()->description;
    }

    /**
     * @return string
     */
    public function getNotation()
    {
        return $this->translate()->notation;
    }

    public function setNotation($notation)
    {
        $this->translate($this->currentLocale, false)->setNotation($notation);
        return $this;
    }

    /**
     * @param bool $published
     *
     * @return $this
     */
    public function setPublished(bool $published)
    {
        $this->published = $published;
        return $this;
    }


    /**
     * @param string $categoryName
     *
     * @return $this
     */
    public function setCategoryName(string $categoryName)
    {
        $this->translate($this->currentLocale, false)->setCategoryName($categoryName);
        return $this;
    }

    public function clearCategoryName()
    {
        $this->translate($this->currentLocale, false)->clearCategoryName();
        return $this;
    }

    public function getCategoryName()
    {
//        return $this->translate()->getCategoryName();
        return $this->translate()->getCategoryName() ? $this->translate()->getCategoryName() : $this->module;
    }

    public function isCategoryName()
    {
        return $this->translate()->getCategoryName() == true;
    }

    /**
     * @param int $rootPosition
     *
     * @return $this
     */
    public function setRootPosition(int $rootPosition): PageEntity
    {
        $this->rootPosition = $rootPosition;
        return $this;
    }




    /**
     * @ORM\PreRemove()
     */
    public function onPreRemove()
    {
//        $this->removeFromPosition();
    }

    /**
     * @return PageEntity|null
     */
    public function getRoot(PageEntity $entity = NULL)
    {
        return $this->root;

        $entity = $entity ? : $this;

        while ($entity->getParent()) {
            $entity = $entity->parent;
        }

        while ($entity->getPrevious()) {
            $entity = $entity->previous;
        }

        return $entity;
    }


    /*
     * trees
     */


    /**
     * @return PageEntity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ArrayCollection|PageEntity[]
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * @return RouteEntity
     */
    public function getMainRoute()
    {
        return $this->mainRoute;
    }


    /**
     * @param $mainRoute
     *
     * @return $this
     */
    public function setMainRoute(RouteEntity $mainRoute)
    {
        $this->mainRoute = $mainRoute;
        return $this;
    }


    /**
     * @param PageEntity $parent
     *
     * @return $this
     */
    public function setParent(PageEntity $parent = NULL, $setPrevious = NULL, PageEntity $previous = NULL)
    {
        $this->parent = $parent;
        return $this;



        if ($parent == $this->getParent() && !$setPrevious) {
            return $this;
        }

        if (!$parent && !$this->getNext() && !$this->getPrevious() && !$this->getParent() && !$setPrevious) {
            return $this;
        }

        if ($setPrevious && $previous === $this) {
            throw new InvalidArgumentException("Previous page is the same as current page.");
        }

        $oldParent = $this->getParent();
        $oldPrevious = $this->getPrevious();
        $oldNext = $this->getNext();

        $this->removeFromPosition();

        if ($parent) {
            $this->parent = $parent;

            if ($setPrevious) {
                if ($previous) {
                    $this->setNext($previous->next);
                    $this->setPrevious($previous);
                } else {
                    $this->setNext($parent->getChildren()->first() ? : NULL);
                }
            } else {
                $this->setPrevious($parent->getChildren()->last() ? : NULL);
            }

            $parent->children[] = $this;
        } else {
            if ($setPrevious) {
                if ($previous) {
                    $this->setNext($previous->next);
                    $this->setPrevious($previous);
                } else {
                    $this->setNext($this->getRoot($oldNext ? : ($oldParent ? : ($oldPrevious))));
                }
            } else {
                $this->parent = NULL;
                $this->previous = NULL;
                $this->next = NULL;
            }
        }

        if ($mainRoute = $this->getMainRoute()) {
            $mainRoute->parent = $this->getParent() && $this->getParent()->getMainRoute() ? $this->getParent()->getMainRoute() : NULL;
            $this->generatePosition();
        }

        return $this;
    }


    public function removeFromPosition()
    {
        if (!$this->getPrevious() && !$this->getNext() && !$this->getParent()) {
            return;
        }

        if ($this->getParent()) {
            foreach ($this->getParent()->getChildren() as $key => $item) {
                if ($item->id === $this->id) {
                    $this->getParent()->children->remove($key);
                    break;
                }
            }
        }

        if ($this->getMainRoute()->getParent()) {
            foreach ($this->mainRoute->parent->getChildren() as $key => $route) {
                if ($route->id === $this->mainRoute->id) {
                    $this->mainRoute->parent->getChildren()->remove($key);
                }
            }
        }

        $next = $this->getNext();
        $previous = $this->getPrevious();

        if ($next) {
            $next->setPrevious($previous, FALSE);
        }

        if ($previous) {
            $previous->setNext($next, FALSE);
        }

        if ($next) {
            $next->generatePosition();
        }

        $this->setPrevious(NULL);
        $this->parent = NULL;
        $this->setNext(NULL);
    }


    /**
     * @return PageEntity
     */
    public function getPrevious()
    {
        return $this->previous;
    }


    public function setNext(PageEntity $next = NULL, $recursively = TRUE)
    {
        if ($next === $this) {
            throw new InvalidArgumentException("Next page is the same as current page.");
        }

        $this->next = $next;

        if ($recursively && $next) {
            $next->setPrevious($this, FALSE);
        }
    }


    public function setPrevious(PageEntity $previous = NULL, $recursively = TRUE)
    {
        if ($previous === $this) {
            throw new InvalidArgumentException("Previous page is the same as current page.");
        }

        $this->previous = $previous;

        if ($recursively && $previous) {
            $previous->setNext($this, FALSE);
        }
    }


    /**
     * @return PageEntity
     */
    public function getNext()
    {
        return $this->next;
    }




    public function generatePosition($recursively = TRUE)
    {
        $position = $this->getPrevious() ? $this->getPrevious()->position + 1 : 1;

        $this->position = $position;
//        $this->positionString = ($this->parent ? $this->parent->positionString . ';' : '') . str_pad($this->position, 3, '0', STR_PAD_LEFT);

        if ($recursively) {
            if ($this->getNext()) {
                $this->getNext()->generatePosition();
            }

            foreach ($this->children as $item) {
                $item->generatePosition();
            }
        }
    }



    public function getPosition()
    {
        return $this->position;
    }



    function __toString()
    {
        return strtolower($this->module) . ':' . strtolower($this->presenter) . ':' . strtolower($this->action);
    }


    function toArray()
    {
        return get_object_vars($this);
    }


}