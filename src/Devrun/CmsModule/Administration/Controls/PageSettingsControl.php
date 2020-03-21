<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    PageSettingsControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Administration\Controls;

use Devrun\Application\UI\Control\Control;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Forms\DevrunForm;
use Devrun\CmsModule\Forms\IDevrunForm;
use Devrun\CmsModule\InvalidArgumentException;
use Devrun\CmsModule\Presenters\PagePresenter;
use Devrun\CmsModule\Repositories\RouteRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Kdyby\Translation\Phrase;
use Nette\Application\UI\Form;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

interface IPageSettingsControlFactory
{
    /** @return PageSettingsControl */
    function create();
}

/**
 * Page Settings, url published etc.
 *
 * Class PageSettingsControl
 *
 * @package Devrun\CmsModule\Administration\Controls
 */
class PageSettingsControl extends Control
{

    /** @var PageEntity */
    private $page;

    /** @var RouteEntity */
    private $route;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var IDevrunForm @inject */
    public $devrunForm;

    /** @var \Nette\Caching\Cache */
    private $cache;

    /** @var string mask for all pages in module */
    private $urlMask = '';


    public function __construct(IStorage $storage)
    {
        $this->monitor(PagePresenter::class, function (PagePresenter $presenter): void {
            $this->page  = $presenter->getPageEntity();
            $this->route = $presenter->getRouteEntity();

            $moduleConfiguration = $presenter->moduleFacade->getModules()[$this->page->getModule()]->getConfiguration();

            if (isset($moduleConfiguration['pagesMask'])) {
                $this->urlMask = $moduleConfiguration['pagesMask'];
            }
        });

        $this->cache = new \Nette\Caching\Cache($storage, 'routes');
    }


    public function render()
    {
        $template = $this->getTemplate();
        $template->package = $this->getPackage();
        $template->validDomain = $this->getValidDomain();
        $template->render();
    }


    public function getValidDomain()
    {
        return $this->routeRepository->getValidDomain($this->getRoute());
    }

    public function isSetValidDomain()
    {
        return $this->routeRepository->isSetValidDomain($this->getRoute());
    }

    /**
     * @return RouteEntity
     */
    private function getRoute(): RouteEntity
    {
        if (!$this->route) {
            throw new InvalidArgumentException('Route not by set, component can run only in PagePresenter');
        }

        return $this->route;
    }

    private function getPackage(): ?PackageEntity
    {
        return $this->getRoute()->getPackage();
    }

    private function isValidDomain(): bool
    {
        return $this->getPackage() && $this->getPackage()->getDomain() && $this->getPackage()->getDomain()->isValid();
    }


    /**
     * @param $name
     *
     * @return DevrunForm
     * @todo translation auto not work yet
     */
    protected function createComponentPageSettingsForm($name)
    {
        $form = $this->devrunForm->create();
        $form->setTranslator($this->translator->domain("admin.forms.$name"));


        $isDomain             = $this->isValidDomain();
        $routeEntity          = $this->getRoute();
        $presenter            = $this->getPresenter();
        $allowedEditPageItems = $presenter->getUser()->isAllowed('Cms:Page', 'editNotations');

        $form->addGroup('Page settings');

        $form->addText('title', 'title')
            ->setDisabled(!$allowedEditPageItems)
            ->setHtmlAttribute('placeholder', "title_placeholder")
            ->addRule(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $form->addTextArea('description', 'description')
            ->setDisabled(!$allowedEditPageItems)
            ->setHtmlAttribute('placeholder', "description_placeholder")
            ->addCondition(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $form->addTextArea('notation', 'notation')
            ->setDisabled(!$allowedEditPageItems)
            ->setHtmlAttribute('placeholder', "notation_placeholder")
            ->addCondition(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        if ($this->presenter->user->isAllowed('Cms:Page', 'published')) {
            $form->addCheckbox('published', 'published');

        } else {
            $form->addHidden('published');
        }


        $route = $form->addContainer("route");

        $route->addText("title", "route_title")
            ->setHtmlAttribute('placeholder', "route_title_placeholder")
            ->addRule(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        if (!$isDomain) {
            $url = $route->addText("url", "url")
                ->setHtmlAttribute('placeholder', "url_placeholder");

            if ($this->urlMask && !$this->isSetValidDomain()) {
                $url->addRule(Form::FILLED, 'required');
                $url->addRule(Form::PATTERN, new Phrase('invalid_url', null, ['mask' => $this->urlMask]), $this->urlMask);
            }

            $defaultMask = "[a-z0-9-/]+";
            $url
                ->addCondition(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255)
                ->addRule(Form::PATTERN, new Phrase('invalid_url', null, ['mask' => $defaultMask]), $defaultMask);

        } else {
            $url = $route->addText("domainUrl", "url")
                ->setHtmlAttribute('placeholder', "url_placeholder");

            if ($this->urlMask && !$this->isSetValidDomain()) {
                $url->addRule(Form::FILLED, 'required');
                $url->addRule(Form::PATTERN, new Phrase('invalid_url', null, ['mask' => $this->urlMask]), $this->urlMask);
            }

            $defaultMask = "[a-z0-9-/]+";
            $url
                ->addCondition(Form::FILLED)
                ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255)
                ->addRule(Form::PATTERN, new Phrase('invalid_url', null, ['mask' => $defaultMask]), $defaultMask);
        }


        $form->addGroup('Route settings');

        $route->addTextArea('description', 'route_description')
            ->setHtmlAttribute('placeholder', "route_description_placeholder")
            ->addCondition(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $route->addTextArea('notation', 'route_notation')
            ->setHtmlAttribute('placeholder', "route_notation_placeholder")
            ->addCondition(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $route->addTextArea('keywords', 'route_keywords')
            ->setHtmlAttribute('placeholder', "route_keywords_placeholder")
            ->addCondition(Form::FILLED, 'required')
            ->addRule(Form::MAX_LENGTH, new Phrase('ruleMaxLength', 255), 255);

        $route->addCheckbox('published', 'published');


        $form->addSubmit('send', 'send')
            ->setHtmlAttribute('data-dismiss', 'modal');




        $form->addFormClass(['ajax']);

        $form->bindEntity($entity = $this->page);
        $routeEntity = $this->getRoute();

        $form->setDefaults([
            'title' => $entity->getTitle(),
            'description' => $entity->getDescription(),
            'notation'    => $entity->getNotation(),
            'route'       => [
                'title'       => $routeEntity->getTitle(),
                'url'         => $routeEntity->getUrl(),
                'domainUrl'   => $routeEntity->getDomainUrl(),
                'keywords'    => $routeEntity->getKeywords(),
                'description' => $routeEntity->getDescription(),
                'notation'    => $routeEntity->getNotation(),
                'published'   => $routeEntity->getPublished(),
            ],
        ]);

        $form->bootstrap3Render();

        $form->onSuccess[] = function (DevrunForm $form, $values) {

            /** @var PageEntity $entity */
            $entity = $form->getEntity();
            $routeEntity = $this->getRoute();

            foreach ($values as $key => $value) {
                if (isset($entity->$key)) {
                    $entity->$key = $value;
                }
            }

            foreach ($values->route as $key => $value) {
                if (isset($routeEntity->$key)) {
                    $routeEntity->$key = $value;
                }
            }


            try {
                /** @var PagePresenter $presenter */
                $presenter = $this->presenter;

                $em = $form->getEntityMapper()->getEntityManager();
                $em->persist($entity)->persist($routeEntity);
                $entity->mergeNewTranslations();
                $routeEntity->mergeNewTranslations();
                $em->flush();

                $this->cache->clean([
                    Cache::TAGS => [RouteEntity::CACHE],
                ]);


                $message = $this->translator->translate("admin.page.updated_successful", null, ['url' => $routeEntity->getUrl()]);
                $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, $this->translator->translate("admin.page.updated_title"), FlashMessageControl::TOAST_SUCCESS);

            } catch (UniqueConstraintViolationException $exception) {
                $message = $this->translator->translate("admin.page.updated_failed", null, ['url' => $routeEntity->getUrl()]);
                $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, $this->translator->translate("admin.page.updated_title"), FlashMessageControl::TOAST_DANGER);
            }

            $presenter->ajaxRedirect('this', null, ['flash']);
        };


        return $form;
    }


}