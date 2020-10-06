<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    CmsExtension.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\DI;

use Devrun\CmsModule\Entities\DomainEntity;
use Devrun\CmsModule\Entities\ImageIdentifyEntity;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\LogEntity;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Devrun\CmsModule\Entities\SettingsEntity;
use Devrun\CmsModule\Entities\UserEntity;
use Devrun\CmsModule\Facades\PageJobs\PageJob;
use Devrun\CmsModule\Forms\LoginTestFormFactory;
use Devrun\CmsModule\InvalidStateException;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Devrun\CmsModule\Repositories\RouteTranslationRepository;
use Devrun\CmsModule\Repositories\UserRepository;
use Devrun\CmsModule\Routes\PageRouteFactory;
use Devrun\CmsModule\Security\Authenticator;
use Devrun\Config\CompilerExtension;
use Devrun\Module\Providers\IPresenterMappingProvider;
use Devrun\Module\Providers\IRouterMappingProvider;
use Devrun\Security\IAuthorizator;
use Devrun\Utils\Debugger;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette;
use Nette\Application\Routers\Route;
use Nette\DI\Extensions\InjectExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class CmsExtension extends CompilerExtension implements IPresenterMappingProvider, IRouterMappingProvider, IEntityProvider
{

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'administration' => Expect::structure([
                'login' => Expect::structure([
                    'name' => Expect::string()->required(),
                    'password' => Expect::string(),
                ]),
                'routePrefix' => Expect::string('admin'),
                'defaultPresenter' => Expect::string('Default'),
                'theme' => Expect::structure([
                    'color' => Expect::anyOf('primary', 'warning', 'info', 'danger', 'success', 'indigo', 'lightblue', 'navy', 'purple', 'fuchsia', 'pink', 'maroon', 'orange', 'lime', 'teal', 'olive')
                                     ->default('primary'),
                    'type' => Expect::anyOf('light', 'dark')->default('light'),
                    'text' => Expect::anyOf('text-xl', 'text-md', 'text-sm', 'text-xs'),
                    'pageMenu' => Expect::anyOf('card-outline', 'card-outline card-outline-tabs'),
                ]),
                'filenameDomain' => Expect::string("%appDir%/config/valid-domains.txt")->assert('is_file'),
                'emailSending' => Expect::bool(false),
                'emailFrom'    => Expect::string('Devrun info <info@devrun.cz>'),
                'modalActions' => Expect::bool(true),
                'autoSyncPages' => Expect::bool(false),
                'treePageMenuFirstLevelVisible' => Expect::bool(true),
                'translateUrl' => Expect::string("http://api.microsofttranslator.com/v2/Http.svc/Translate"), //Application Translate Url
                'emptyImage' => Expect::structure([
                    'font'     => Expect::string('resources/cmsModule/fonts/OpenSansEasy/OpenSans-Regular.ttf')->assert('is_file'),
                    'text'     => Expect::string('upload image'),
                    'fontSize' => Expect::int(36),
                    'width'    => Expect::int(640),
                    'height'   => Expect::int(480),
                ]),
            ]),

            'website' => Expect::structure([
                'name' => Expect::string('Blog'),
                'title' => Expect::string('%n %s %t'),
                'titleSeparator' => Expect::string('|'),
                'keywords' => Expect::string(),
                'description' => Expect::string(),
                'author' => Expect::string(),
                'robots' => Expect::string('index, follow'),
                'routePrefix' => Expect::string(),
                'oneWayRoutePrefix' => Expect::string(),
                'languages' => Expect::array(),
                'defaultLanguage' => Expect::string('cs'),
                'domainIPs' => Expect::array(),
                'defaultDomain' => Expect::string(),
                'defaultPresenter' => Expect::string('Homepage'),
                'errorPresenter' => Expect::string('Cms:Error'),
                'layout' => Expect::string('cms/bootstrap'),
                'theme' => Expect::string('default'),
            ]),
        ]);
    }


    public function loadConfiguration()
    {
        parent::loadConfiguration();

        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();

        /** @var \stdClass $config */
        $config  = $this->getConfig();

        $builder->addDefinition($this->prefix('manager.administrationManager'))
            ->setFactory('Devrun\CmsModule\Administration\AdministrationManager', [
                $config->administration->defaultPresenter,
                $config->administration->login,
                $config->administration->theme
            ]);



        /* @deprecated CMS old route for nette <3
        // detect prefix
        $prefix = $config->website->routePrefix;
        $adminPrefix = $config->administration->routePrefix;
        $languages = $config->website->languages;
        $prefix = str_replace('<lang>/', '<lang ' . implode('|', $languages) . '>/', $prefix);


        $parameters = array();
        $parameters['lang'] = count($languages) > 1 || $config['website']['routePrefix'] ? NULL : $config['website']['defaultLanguage'];


         $builder->addDefinition($this->prefix('pageRoute'))
            ->setFactory('Devrun\CmsModule\Routes\PageRoute', array($prefix, $parameters, $config->website->defaultLanguage, $config->website->languages, $config->website->defaultDomain))
            ->setAutowired(false)
            ->setInject(true)
            ->addTag('route', array('priority' => 100))
            ->addTag(EventsExtension::TAG_SUBSCRIBER);*/


        $builder->addDefinition($this->prefix('route'))
            //->setFactory("Devrun\CmsModule\Routes\PageRouteFactory::createRouter")
            ->setType("Devrun\CmsModule\Routes\PageRouteFactory")
            ->addTag(EventsExtension::TAG_SUBSCRIBER)
            ->addTag('route', array('priority' => 100)); // tag route is not use yet


        /*
         * system
         */
        $builder->addDefinition($this->prefix('authenticator'))
                ->setFactory(Authenticator::class, [$config->administration->login->name, $config->administration->login->password])
                ->setInject();


        /*
         * presenters
         */
        $builder->addDefinition($this->prefix('presenters.users'))
            ->setFactory('Devrun\CmsModule\Presenters\UsersPresenter')
            ->addSetup('setEmailSending', [$config->administration->emailSending])
            ->addSetup('setEmailFrom', [$config->administration->emailFrom])
            ->addTag('devrun.presenter')
            ->addTag('administration', [
                'link'        => ':Cms:Users:default',
                'icon'        => 'fa-users',
                'category'    => 'Content',
                'name'        => 'Users',
                'description' => 'Manage users, edit roles...',
                'priority'    => 20,

            ]);


        $builder->addDefinition($this->prefix('presenters.login'))
                ->setType('Devrun\CmsModule\Presenters\LoginPresenter')
                ->addSetup('setWebsiteInfo', [$config->website]);

        $builder->addDefinition($this->prefix('presenters.admin'))
                ->setType(AdminPresenter::class)
                ->addSetup('setWaebsiteInfo', [$config->website]);




        /*
         * form factory
         */
        $builder->addDefinition($this->prefix('forms.loginTestFormFactory'))
                ->setType(LoginTestFormFactory::class);


        $builder->addDefinition($this->prefix('forms.devrunFormFactory'))
            ->setFactory('Devrun\CmsModule\Forms\DevrunFormFactory');

        $builder->addFactoryDefinition($this->prefix('form.imagesFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IImagesFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('form.packageFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IPackageSelectFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);


        $builder->addFactoryDefinition($this->prefix('form.imageFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IImageFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('form.loginForm'))
            ->setImplement('Devrun\CmsModule\Forms\ILoginFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('form.registrationForm'))
            ->setImplement('Devrun\CmsModule\Forms\IRegistrationFormFactory')
            ->addSetup('setEmailSending', ['emailSending' => $config->administration->emailSending])
            ->addSetup('setEmailFrom', ['emailFrom' => $config->administration->emailFrom])
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('form.forgottenPasswordForm'))
            ->setImplement('Devrun\CmsModule\Forms\IForgottenPasswordFormFactory')
            ->addSetup('setEmailSending', ['emailSending' => $config->administration->emailSending])
            ->addSetup('setEmailFrom', ['emailFrom' => $config->administration->emailFrom])
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('form.profileFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IProfileFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);




        /*
         * controls
         */
        $builder->addFactoryDefinition($this->prefix('control.navigationPage'))
            ->setImplement('Devrun\CmsModule\Controls\INavigationPageControlFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.environment'))
            ->setImplement('Devrun\CmsModule\Controls\IJSEnvironmentControl');


        $builder->addFactoryDefinition($this->prefix('control.pagesNestable'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\INestablePagesControl')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.settingsControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\ISettingsControlFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.rawImagesControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\IRawImagesControlFactory')
            ->addSetup('setImageDir', [$builder->parameters['wwwDir'] . 'images' . DIRECTORY_SEPARATOR]);

        $builder->addFactoryDefinition($this->prefix('control.flashMessageControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IFlashMessageControlFactory');

        $builder->addFactoryDefinition($this->prefix('control.packageControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IPackageControlFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.themeControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IThemeControlFactory')
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.modalActionControl'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\IModalActionControlFactory')
            ->addSetup('setEnable', [$config->administration->modalActions])
            ->addTag(InjectExtension::TAG_INJECT);

        $builder->addFactoryDefinition($this->prefix('control.treePageControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\ITreePagesControlFactory')
            ->addSetup('setFirstLevelVisible', [$config->administration->treePageMenuFirstLevelVisible])
            ->addTag(InjectExtension::TAG_INJECT)
            ->addTag('devrun.control')
            ->addTag('administration', [
                'category' => 'Content',
                'name'     => 'TreePagesControl',
                'priority' => 5
            ]);

        $builder->addFactoryDefinition($this->prefix('control.carouselItemsControl'))
                ->setImplement('Devrun\CmsModule\Controls\ICarouselItemsControlFactory')
                ->addTag(InjectExtension::TAG_INJECT);



//        $builder->addDefinition($this->prefix('control.nestablePagesEditControl'))
//            ->setImplement('Devrun\CmsModule\Administration\Controls\INestablePagesEditControl')
//            ->setInject(true);



//        $builder->addDefinition($this->prefix('control.languages'))
//            ->setImplement('Devrun\CmsModule\Administration\Controls\ILanguageControlFactory');


        /*
         * entities with services
         */




        /*
         * repositories
         */
        $builder->addDefinition($this->prefix('repository.user'))
            ->setFactory(UserRepository::class)
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, UserEntity::class);

        $builder->addDefinition($this->prefix('repository.page'))
            ->setFactory('Devrun\CmsModule\Repositories\PageRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, PageEntity::class);


        $builder->addDefinition($this->prefix('repository.route'))
            ->setFactory('Devrun\CmsModule\Repositories\RouteRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, RouteEntity::class)
            ->addSetup('setDefaultDomain', [$config->website->defaultDomain]);

        $builder->addDefinition($this->prefix('repository.routeTranslation'))
            ->setFactory(RouteTranslationRepository::class)
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, RouteTranslationEntity::class);


        $builder->addDefinition($this->prefix('repository.image'))
            ->setFactory('Devrun\CmsModule\Repositories\ImageRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, ImagesEntity::class);

        $builder->addDefinition($this->prefix('repository.imageIdentify'))
            ->setFactory('Devrun\CmsModule\Repositories\ImageIdentifyRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, ImageIdentifyEntity::class);

        $builder->addDefinition($this->prefix('repository.settings'))
            ->setFactory('Devrun\CmsModule\Repositories\SettingsRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, SettingsEntity::class);

        $builder->addDefinition($this->prefix('repository.package'))
            ->setFactory('Devrun\CmsModule\Repositories\PackageRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, PackageEntity::class)
            ->setInject(true);

        $builder->addDefinition($this->prefix('repository.log'))
            ->setFactory('Devrun\CmsModule\Repositories\LogRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, LogEntity::class);

        $builder->addDefinition($this->prefix('repository.domain'))
            ->setFactory('Devrun\CmsModule\Repositories\DomainRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, DomainEntity::class);



        /*
         * facades
         */
        $builder->addDefinition($this->prefix('facade.translate'))
            ->setFactory('Devrun\CmsModule\Facades\TranslateFacade');

        $builder->addDefinition($this->prefix('facade.page'))
            ->setFactory('Devrun\CmsModule\Facades\PageFacade');

        $builder->addDefinition($this->prefix('facade.package'))
            ->setFactory('Devrun\CmsModule\Facades\PackageFacade', [$builder->parameters['wwwDir']]);

        $builder->addDefinition($this->prefix('facade.domain'))
            ->setFactory('Devrun\CmsModule\Facades\DomainFacade', [$config->administration->filenameDomain, $config->website->domainIPs]);

        $builder->addDefinition($this->prefix('facade.settings'))
            ->setFactory('Devrun\CmsModule\Facades\SettingsFacade');


        $builder->addDefinition($this->prefix('facade.theme'))
            ->setFactory('Devrun\CmsModule\Facades\ThemeFacade', [$builder->parameters['modules'], $builder->parameters['wwwDir']]);

        $builder->addDefinition($this->prefix('facade.image'))
            ->setFactory('Devrun\CmsModule\Facades\ImageManageFacade')
            ->addSetup('setConfigEmptyImage', [(array)$config->administration->emptyImage])
            ->addTag(EventsExtension::TAG_SUBSCRIBER)
            ->setInject(true);

        /*
         * jobs
         */
        $builder->addDefinition($this->prefix('job.synchronizePage'))
            ->setFactory('Devrun\CmsModule\Facades\PageJobs\SynchronizePagesJob');

        $builder->addDefinition($this->prefix('job.page'))
                ->setFactory(PageJob::class, ['wwwDir' => $builder->parameters['wwwDir']]);



        /*
         * storages
         */
        $builder->addDefinition($this->prefix('imageManageStorage'))
            ->setFactory('Devrun\CmsModule\Storage\ImageManageStorage', [$builder->parameters['autoFlush']])
            ->addTag(EventsExtension::TAG_SUBSCRIBER);



        /*
         * listeners
         */
        if (!Debugger::isConsole()) {
        }
        $builder->addDefinition($this->prefix('listener.page'))
            ->setFactory('Devrun\CmsModule\Listeners\PageListener', [$config->administration->autoSyncPages])
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

        $builder->addDefinition($this->prefix('listener.imagePresenter'))
            ->setFactory('Devrun\CmsModule\Listeners\ImagePresenterListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

        $builder->addDefinition($this->prefix('listener.packageListener'))
            ->setFactory('Devrun\CmsModule\Listeners\PackageListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

        $builder->addDefinition($this->prefix('listener.imageListener'))
            ->setFactory('Devrun\CmsModule\Listeners\ImageListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

        $builder->addDefinition($this->prefix('listener.logListener'))
            ->setFactory('Devrun\CmsModule\Listeners\LogListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);


        /*
         * commands
         */
        $commands = array(
//            'pageCreate' => 'Devrun\CmsModule\Commands\PageCreate',
            'pageUpdate' => 'Devrun\CmsModule\Commands\PageUpdate',
//            'pageDrop' => 'Devrun\CmsModule\Commands\PageDrop',
        );
        foreach ($commands as $name => $cmd) {
            $builder->addDefinition($this->prefix(lcfirst($name) . 'Command'))
                ->setFactory("{$cmd}Command")
                ->addTag(ConsoleExtension::TAG_COMMAND);
        }


        /*
        * controls
        */
//        $builder->addDefinition($this->prefix('controls.webLoaderCssControlFactory'))
//            ->setImplement('Devrun\Controls\IWebLoaderCssControl')
//            ->addSetup('setTempDir', [$builder->parameters['tempDir']])
//            ->addSetup('setWwwDir', [$builder->parameters['wwwDir']]);

        /*
                $builder->addDefinition($this->prefix('controls.webLoaderJsControlFactory'))
                    ->setImplement('Devrun\Controls\IWebLoaderJsControl')
                    ->addSetup('setTempDir', [$builder->parameters['tempDir']])
                    ->addSetup('setWwwDir', [$builder->parameters['wwwDir']]);
        */

    }

    public function beforeCompile()
    {
        parent::beforeCompile();
        $this->registerAdministrationPages();
        $this->registerAdministrationControls();
        $this->registerAdministrationFactories();
        $this->registerAdministrationPermissions();

        $builder = $this->getContainerBuilder();
        $config = $this->getConfig();

        $registerToLatte = function (Nette\DI\Definitions\FactoryDefinition $def) {
            $def->addSetup('?->onCompile[] = function($engine) { Devrun\CmsModule\Macros\UICmsMacros::install($engine->getCompiler()); }', ['@self']);
        };

        $latteFactoryService = $builder->getByType('Nette\Bridges\ApplicationLatte\ILatteFactory') ?: 'nette.latteFactory';

        /** @var Nette\DI\ServiceDefinition $q */
        $q = $builder->getDefinition('nette.latteFactory');

        $qw = $q->getSetup();

//        unset($qw[5]);

//        dump($qw);

//        $q->setSetup($qw);

//        dump($q);
//        die();



        if ($builder->hasDefinition($latteFactoryService)) {
            $registerToLatte($builder->getDefinition($latteFactoryService));
        }

//        if ($builder->hasDefinition('nette.latte')) {
//            $registerToLatte($builder->getDefinition('nette.latte'));
//        }




    }

    public function afterCompile(Nette\PhpGenerator\ClassType $class)
    {
        parent::afterCompile($class);

        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();
        $config  = $this->getConfig();

        $dir = $builder->parameters['tempDir'] . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "_pages";

        if (!is_dir($dir)) {
            umask(0000);
            @mkdir($dir, 0777, true);
        }


//        dump($builder);
//        dump($config);
//        die();


    }


    private function registerAdministrationPermissions()
    {
        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();

        $userPermissions      = $builder->findByTag(IAuthorizator::TAG_USER_PERMISSION);
        $manager = $builder->getDefinition($this->prefix('manager.administrationManager'));

        foreach ($userPermissions as $service => $tag) {

            /** @var Nette\DI\ServiceDefinition $definition */
            $definition = $builder->getDefinition($service);

            if (!method_exists($definition->getType(), $method = 'setPermissions')) {
                throw new InvalidStateException("{$definition->getType()} implement Devrun\Security\IAuthorizator for $method please");
            }
            $definition->addSetup('setPermissions');
            $manager->addSetup('addUserPermission', [$service, [$tag]]);
        }
    }


    private function registerAdministrationPages()
    {
        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();
        $config  = $this->getConfig();
        $manager = $builder->getDefinition($this->prefix('manager.administrationManager'));

        $presenters      = $builder->findByTag('devrun.presenter');
        $administrations = $builder->findByTag('administration');

        foreach (array_intersect_key($administrations, $presenters) as $service => $tags) {
            $definition = $builder->getDefinition($service);

            if (method_exists($definition->getClass(), 'setWebLoaderCollections')) {
                $definition->addSetup('setWebLoaderCollections', [$config['webloader']]);
            }

            $tags = $builder->getDefinition($service)->getTag('administration');
            $manager->addSetup('addAdministrationPage', [$service, $tags]);
        }
    }


    private function registerAdministrationControls()
    {
        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();
        $manager = $builder->getDefinition($this->prefix('manager.administrationManager'));

        $controls      = $builder->findByTag('devrun.control');
        $administrations = $builder->findByTag('administration');

        foreach (array_intersect_key($administrations, $controls) as $service => $tags) {
            $tags = $builder->getDefinition($service)->getTag('administration');
            $manager->addSetup('addAdministrationComponent', [$service, $tags]);
        }
    }


    private function registerAdministrationFactories()
    {
        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();
        $manager = $builder->getDefinition($this->prefix('manager.administrationManager'));

        $controls      = $builder->findByTag('devrun.factory');
        $administrations = $builder->findByTag('administration');

        foreach (array_intersect_key($administrations, $controls) as $service => $tags) {
            $tags = $builder->getDefinition($service)->getTag('administration');
            $manager->addSetup('addAdministrationFactory', [$service, $tags]);
        }
    }


    /**
     * Returns array of ServiceDefinition,
     * that will be appended to setup of router service
     *
     * @return \Nette\Application\IRouter
     */
    public function getRoutesDefinition()
    {
        global /** @var Nette\DI\Container $container */
        $container;

        /** @var PageRouteFactory $service */
        $service = $container->getService('cms.route');

        /** @var Nette\Application\IRouter $routeList */
        $routeList = $service->create();

        /*
         * default route
         */
        $routeList[] = new Route("[<locale={$service->getDefaultLocale()} {$service->getLocales()}>/]<presenter>/<action>[/<id>]", 'Homepage:default');
        return $routeList;
    }

    /**
     * Returns array of ClassNameMask => PresenterNameMask
     *
     * @example return array('*' => 'Booking\*Module\Presenters\*Presenter');
     * @return array
     */
    public function getPresenterMapping()
    {
        return array(
            'Cms' => "Devrun\\CmsModule\\*Module\\Presenters\\*Presenter",
        );
    }

    /**
     * Returns associative array of Namespace => mapping definition
     *
     * @return array
     */
    function getEntityMappings()
    {
        return array(
            'Devrun\CmsModule' => dirname(__DIR__) . '/Entities/',
        );
    }
}