<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2016
 *
 * @file    AdminPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\Application\UI\Presenter\BasePresenter;
use Devrun\CmsModule\Administration\AdministrationManager;
use Devrun\CmsModule\Administration\Controls\IModalActionControlFactory;
use Devrun\CmsModule\Administration\Controls\SettingsControl;
use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Controls\IFlashMessageControlFactory;
use Devrun\CmsModule\Controls\IJSEnvironmentControl;
use Devrun\CmsModule\Controls\INavigationPageControlFactory;
use Devrun\CmsModule\Facades\PageFacade;
use Devrun\CmsModule\Facades\SettingsFacade;
use Devrun\Doctrine\DoctrineForms\EntityFormMapper;
use Devrun\Doctrine\Entities\UserEntity;
use Devrun\Facades\UserFacade;
use Devrun\Module\ModuleFacade;
use Devrun\ServiceNotFoundException;
use Devrun\Utils\Arrays;
use Nette;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * Class AdminPresenter
 *
 * @property-read AdministrationManager $administrationManager
 * @package       Devrun\CmsModule\Presenters
 */
class AdminPresenter extends BasePresenter
{

    /** @var bool */
    protected $__installation;

    /** @var AdministrationManager @inject */
    public $administrationManager;

    /** @var PageFacade @inject */
    public $pageFacade;

    /** @var ModuleFacade @inject */
    public $moduleFacade;

    /** @var SettingsFacade @inject */
    public $settingsFacade;

    /** @var UserFacade @inject */
    public $userFacade;

    /** @var EntityFormMapper @inject */
    public $entityFormMapper;

    /** @var INavigationPageControlFactory @inject */
    public $navigationPageControlFactory;

    /** @var IJSEnvironmentControl @inject */
    public $environmentControlFactory;

    /** @var IFlashMessageControlFactory @inject */
    public $flashMessageControl;

    /** @var IModalActionControlFactory @inject */
    public $modalActionControlFactory;

    /** @var UserEntity */
    private $userEntity;

    /** @var array modules */
    private $modules = [];

    /** @var int @persistent */
    public $package;

    /** @var string @persistent */
    public $layoutAjax;

    public $signaled = false;


    protected function startup()
    {
        parent::startup();


//        dump($this->administrationManager->login);
//        die();

        // check admin account
        if (!$this->administrationManager->login['name']) {
            if ($this->getName() != 'Cms:Admin:Installation') {
                $this->redirect(':Cms:Admin:Installation:');
            }
            $this->setView('account');
            $this->__installation = true;
            $this->flashMessage($this->translator->translate('Please set administrator\'s account.'), 'warning');
        } // end

        $user = $this->getUser();

        /*
         * get service user permission to start setPermissions
         */
        foreach ($this->administrationManager->getUserPermissions() as $service => $tag) {
            $this->context->getService($service);
        }

        try {
            if (!$user->isAllowed($this->name, $this->action)) {
                //$this->flashMessage($message, 'warning');
                $this->getUser()->logout();
                $this->redirect(':Cms:Login:', array('backlink' => $this->storeRequest()));
            }

        } catch (Nette\InvalidStateException $e) {

            dump($e);
            throw new Nette\InvalidStateException($e->getMessage());
        }

        if ($this->getUser()->isLoggedIn()) {
            $this->userEntity = $this->userFacade->getUserRepository()->find($this->getUser()->getId());
        }

        $this->modules = $this->context->getParameters()['modules'];

        $this->getPages();

        $action                        = ($this->action != 'default') ? ' ' . $this->action : null;
        $this->template->pageIdName    = str_replace(':', '', $this->name);
        $this->template->pageClassName = $action;

        if ($this->getSignal() !== null) {
            $this->signaled = true;
        }
    }


    protected function beforeRender()
    {
        parent::beforeRender();

        foreach ($this->modules as $module => $params) {
            $key                  = "{$module}Path";
            $this->template->$key = $params['path'];
        }

        $this->template->navbarLeftAdminItems  = $this->administrationManager->getAdministrationItemsByCategory('NavbarLeft');
        $this->template->navbarRightAdminItems = $this->administrationManager->getAdministrationItemsByCategory('NavbarRight');
        $this->template->contentAdminItems     = $this->administrationManager->getAdministrationItemsByCategory('Content');

        $systemPages = $this->administrationManager->getAdministrationPagesByCategory('system', $isPrefix = true);
        $modulePages = $this->administrationManager->getAdministrationPagesByCategory('module', $isPrefix = true);

        $moduleItems = $systemAdminItems = [];
        foreach ($modulePages as $modulePage) {
            $categories  = explode('.', $modulePage['category']);
            $moduleItems = Arrays::addByArrayKeys($moduleItems, $categories, $modulePage);
        }
        foreach ($systemPages as $systemPage) {
            $categories       = explode('.', $systemPage['category']);
            $systemAdminItems = Arrays::addByArrayKeys($systemAdminItems, $categories, $systemPage);
        }

        $this->template->cmsVersion       = $this->moduleFacade->getModules()['cms']->getVersion();
        $this->template->moduleAdminItems = $moduleItems;
        $this->template->systemAdminItems = $systemAdminItems;
    }


    protected function isAjaxLayout()
    {
        if ($layoutAjax = $this->getParameter('layoutAjax'))
            $this->layoutAjax = $layoutAjax;

        if ($this->layoutAjax && !$this->isAjax())
            $this->layoutAjax = false;

        return ($this->isAjax() && $this->layoutAjax);
    }


    protected function afterRender()
    {
        parent::afterRender();

        if ($this->isAjax() && ($layoutAjax = $this->getParameter('layoutAjax')) == true) {

            if (!file_exists($layoutFile = __DIR__ . "/templates/@$layoutAjax.latte")) {
                Debugger::log(__METHOD__ . " - ajaxLayout `$layoutFile`` not found", ILogger::WARNING);
                $layoutFile = false;
            }
            $this->setLayout($layoutFile);

        } elseif ($this->isAjax() && !$this->signaled && !$this->isControlInvalid()) {
            $this->redrawControl('content');
            $this->redrawControl('styles');
        }
    }


    public function formatLayoutTemplateFiles()
    {
        $cmsLayout = $this->modules['cms']['path'] . "src/Devrun/CmsModule/Presenters/templates/@layout.latte";

        $return   = parent::formatLayoutTemplateFiles();
        $return[] = $cmsLayout;
        return $return;
    }


    public function getPages()
    {

        return;

        $pages = $this->pageFacade->findPublicPages();
        dump($pages);


        $pages = $this->pageFacade->getPageRepository()->childrenHierarchy(null, true, array(
            'decorate'            => true,
            'representationField' => 'name',
            'nodeDecorator'       => function ($node) {
                return '<a href="/page/' . $node['name'] . '">' . $node['name'] . '</a>';
            },
            'html'                => true
        ));

        dump($pages);

        $query = $this->pageFacade->getPageRepository()->childrenQueryBuilder()->getQuery()->getArrayResult();

        dump($query);


        $options = [
            'decorate' => true,
        ];

        $pages = $this->pageFacade->getPageRepository()->buildTree($query, $options);

        dump($pages);


        die();


    }


    /**
     * @param $name
     *
     * @return DataGrid
     */
    public function createGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setItemsPerPageList([20, 30, 50, 100]);
        return $grid;
    }


    /**
     * @return UserEntity
     */
    public function getUserEntity()
    {
        return $this->userEntity;
    }


    public function handleLogout()
    {
        $this->getUser()->logout();
        $this->flashMessage('Byl jste odhlášen ze systému', 'info');
        $this->ajaxRedirect(':Cms:Login:');
    }


    public function isAllowedLink($link)
    {
        $allowed = false;
        $link    = trim($link, ':');
        if (count($exp = explode(':', $link)) > 2) {
            $privilege = end($exp);
            unset($exp[count($exp) - 1]);
            $resource = implode(":", $exp);
            $allowed  = $this->user->isAllowed($resource, $privilege);
        }

        return $allowed;
    }


    /**
     * @deprecated use nestablePagesEditControl
     * @return \Devrun\CmsModule\Controls\NavigationPageControl
     */
    protected function createComponentNavigationPageControl()
    {
        $control = $this->navigationPageControlFactory->create();

        $options = array(
            'decorate'            => true,
            'rootOpen'            => '<ul class="treeview-menu">',
            'representationField' => 'name',
            'childOpen'           => function ($node) {
                $class = $this->isLinkCurrent(":Cms:Page:edit", ['id' => $node['id']]) ? 'active' : '';
                return "<li class='$class'>";
            },
            'nodeDecorator'       => function ($node) {
                return '<a href="' . $this->link(":Cms:Page:edit", ['id' => $node['id']]) . '">' . '<i class="fa fa-circle-o"></i>' . $node['title'] . '</a>';
            },
            'html'                => true
        );

        $query = $this->pageFacade->getPageRepository()->createQueryBuilder('a')
            ->addOrderBy('a.root')
            ->addOrderBy('a.lft')
            ->getQuery();

        $control
            ->setQuery($query)
            ->setOptions($options);

        return $control;
    }


    protected function createComponentBootstrapNavigationPageControl()
    {
        $control = $this->navigationPageControlFactory->create();

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
                return '<a href="' . $this->getPresenter()->link($node['mainRoute']['uri']) . '">' . $node['name'] . '</a>';
            },
        );

        $query = $this->pageFacade->getPageRepository()->createQueryBuilder('a')
            ->addSelect('r')
            ->join('a.mainRoute', 'r')
            ->addOrderBy('a.root')
            ->addOrderBy('a.lft')
            ->getQuery();

        $control
            ->setQuery($query)
            ->setOptions($options);

        return $control;

    }


    /**
     * @return Nette\Application\UI\Multiplier
     */
    protected function createComponentAdministrationItemControls()
    {
        return new Nette\Application\UI\Multiplier(function ($serviceName) {
            $sn = str_replace('_', '.', $serviceName);
            if ($this->context->hasService($sn)) {
                $service = $this->context->getService($sn);
                return $service->create();
            }

            throw new ServiceNotFoundException("$sn not found");
        });
    }


    /**
     * @return \Devrun\CmsModule\Administration\Controls\ModalActionControl
     */
    protected function createComponentModalActionControl()
    {
        return $this->modalActionControlFactory->create();
    }


    /**
     * @return \Devrun\CmsModule\Controls\JSEnvironmentControl
     */
    protected function createComponentEnvironmentControl()
    {
        return $this->environmentControlFactory->create();
    }

    /**
     * @return \Devrun\CmsModule\Controls\FlashMessageControl
     */
    protected function createComponentFlashMessageControl()
    {
        return $this->flashMessageControl->create();
    }


    public function flashMessage($message, $type = 'info', $title = '', array $options = array())
    {
        if ($type == FlashMessageControl::TOAST_TYPE) {
            $id         = $this->getParameterId('flash');
            $messages   = $this->getPresenter()->getFlashSession()->$id;
            $messages[] = $flash = (object)array(
                'message' => $message,
                'title'   => $title,
                'type'    => $type,
                'options' => $options,
            );
            $this->getTemplate()->flashes = $messages;
            $this->getPresenter()->getFlashSession()->$id = $messages;
            return $flash;
        }

        return parent::flashMessage($message, $type);
    }


}