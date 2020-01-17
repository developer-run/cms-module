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
use Devrun\CmsModule\Entities\SettingsEntity;
use Devrun\CmsModule\Entities\UserEntity;
use Devrun\CmsModule\InvalidStateException;
use Devrun\CmsModule\Repositories\UserRepository;
use Devrun\CmsModule\Security\Authenticator;
use Devrun\Config\CompilerExtension;
use Devrun\Security\IAuthorizator;
use Devrun\Utils\Debugger;
use Flame\Modules\Providers\IPresenterMappingProvider;
use Flame\Modules\Providers\IRouterProvider;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class CmsExtension extends CompilerExtension implements IPresenterMappingProvider, IRouterProvider, IEntityProvider
{

    public $defaults = array(
        'emailSending' => true,
        'emailFrom'    => 'Soutěže <info@souteze.pixman.cz>',
        'administration' => array(
            'login'            => array(
                'name'     => '',
                'password' => ''
            ),
            'routePrefix'      => 'admin',
            'defaultPresenter' => 'Default',
            'theme'            => 'cms',
            'treePageMenuFirstLevelVisible' => true,
            'filenameDomain' => "%appDir%/config/valid-domains.txt",
        ),

        'website' => array(
            'name' => 'Blog',
            'title' => '%n %s %t',
            'titleSeparator' => '|',
            'keywords' => '',
            'description' => '',
            'author' => '',
            'robots' => 'index, follow',
            'routePrefix' => '',
            'oneWayRoutePrefix' => '',
            'languages' => array(),
            'defaultLanguage' => 'cs',
            'domainIPs' => [],
            'defaultDomain' => 'souteze.pixman.cz',
            'defaultPresenter' => 'Homepage',
            'errorPresenter' => 'Cms:Error',
            'layout' => 'cms/bootstrap',
            'cacheMode' => '',
            'cacheValue' => '10',
            'theme' => '',
        ),

        'webloader'      => [
            'outputDir' => '%wwwCacheDir%',
            'tempUri'   => 'webTemp',
            'panelName' => 'Admin',
            'css'       => [
                'files' => [
                    'assets/AdminLTE/bootstrap/css/bootstrap.css',
                    'assets/AdminLTE/dist/css/adminLTE.css',
                    'assets/font-awesome/css/font-awesome.css',
                    'assets/AdminLTE/dist/css/skins/_all-skins.min.css',
                    'assets/AdminLTE/plugins/iCheck/flat/blue.css',
                    'assets/AdminLTE/plugins/morris/morris.css',
                    'assets/AdminLTE/plugins/jvectormap/jquery-jvectormap-1.2.2.css',
                    'assets/AdminLTE/plugins/datepicker/datepicker3.css',
                    'assets/AdminLTE/plugins/daterangepicker/daterangepicker.css',
                    'assets/AdminLTE/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css',
                    'assets/grido/css/grido.css',
                ],
            ],

            'js' => [
                'files' => [
                    'assets/AdminLTE/plugins/jQuery/jquery-2.2.3.min.js',
                    'assets/AdminLTE/bootstrap/js/bootstrap.min.js',
                    'assets/AdminLTE/plugins/morris/morris.min.js',
                    'assets/AdminLTE/plugins/sparkline/jquery.sparkline.min.js',
                    'assets/AdminLTE/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js',
                    'assets/AdminLTE/plugins/jvectormap/jquery-jvectormap-world-mill-en.js',
                    'assets/AdminLTE/plugins/knob/jquery.knob.js',
                    'assets/moment/moment.js',
                    'assets/moment/locale/cs.js',
                    'assets/AdminLTE/plugins/daterangepicker/daterangepicker.js',
                    'assets/AdminLTE/plugins/datepicker/bootstrap-datepicker.js',
                    'assets/AdminLTE/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js',
                    'assets/AdminLTE/plugins/slimScroll/jquery.slimscroll.min.js',
                    'assets/AdminLTE/plugins/fastclick/fastclick.js',
                    'assets/AdminLTE/dist/js/app.min.js',
                    'assets/nette.ajax.js/nette.ajax.js',
                    'assets/nette/live-form-validation.js',
                    'assets/grido/js/grido.js',
                    'assets/grido/js/plugins/grido.nette.ajax.js',
                    "%modules.cms.path%/resources/assets/extensions/popupDialogExtension.js",
                ],
            ],
        ],

        'content' => [
            'modalActions' => true,
            'images' => [],
            'emptyImage' => [
                'font'     => 'resources/cmsModule/fonts/OpenSansEasy/OpenSans-Regular.ttf',
                'text'     => 'upload image',
                'fontSize' => 36,
                'width'    => 640,
                'height'   => 480,
            ],
        ],

        'options' => [
            'autoSyncPages'=> false, // PageListener , presenter -> onStartup event
            'fromLanguage' => "en",
            'toLanguage'   => "cs",
            'contentType'  => 'text/plain',
            'category'     => 'general',
            'translateUrl' => "http://api.microsofttranslator.com/v2/Http.svc/Translate", //Application Translate Url
        ],

    );


    public function loadConfiguration()
    {
        parent::loadConfiguration();

        /** @var Nette\DI\ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();
        $config  = $this->getConfig($this->defaults);

        $builder->addDefinition($this->prefix('manager.administrationManager'))
            ->setFactory('Devrun\CmsModule\Administration\AdministrationManager', [
                $config['administration']['defaultPresenter'],
                $config['administration']['login'],
                $config['administration']['theme']
            ]);



        // detect prefix
        $prefix = $config['website']['routePrefix'];
        $adminPrefix = $config['administration']['routePrefix'];
        $languages = $config['website']['languages'];
//        $prefix = str_replace('<lang>/', '<lang ' . implode('|', $languages) . '>/', $prefix);


        $parameters = array();
//        $parameters['lang'] = count($languages) > 1 || $config['website']['routePrefix'] ? NULL : $config['website']['defaultLanguage'];


        // CMS route
        $builder->addDefinition($this->prefix('pageRoute'))
            ->setFactory('Devrun\CmsModule\Routes\PageRoute', array($prefix, $parameters, $config['website']['defaultLanguage'], $config['website']['languages'], $config['website']['defaultDomain']))
            ->setAutowired(false)
            ->setInject(true)
            ->addTag('route', array('priority' => 100))
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

/*
        if ($config['website']['oneWayRoutePrefix']) {
            $container->addDefinition($this->prefix('oneWayPageRoute'))
                ->setClass('CmsModule\Content\Routes\PageRoute', array('@container', '@cacheStorage', '@doctrine.checkConnectionFactory', $config['website']['oneWayRoutePrefix'], $parameters, $config['website']['languages'], $config['website']['defaultLanguage'], TRUE)
                )
                ->addTag('route', array('priority' => 99));
        }
*/

        /*
         * system
         */
        $builder->addDefinition($this->prefix('authenticator'))
                ->setFactory(Authenticator::class, [$config['administration']['login']['name'], $config['administration']['login']['password']])
                ->setInject();


        /*
         * presenters
         */
        $builder->addDefinition($this->prefix('presenters.users'))
            ->setFactory('Devrun\CmsModule\Presenters\UsersPresenter')
            ->addSetup('setEmailSending', [$config['emailSending']])
            ->addSetup('setEmailFrom', [$config['emailFrom']])
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
                ->addSetup('setWebsiteInfo', [$config['website']]);




        /*
         * form factory
         */
        $builder->addDefinition($this->prefix('forms.devrunFormFactory'))
            ->setFactory('Devrun\CmsModule\Forms\DevrunFormFactory');

        $builder->addDefinition($this->prefix('form.imagesFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IImagesFormFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('form.packageFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IPackageSelectFormFactory')
            ->setInject(true);


        $builder->addDefinition($this->prefix('form.imageFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IImageFormFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('form.loginForm'))
            ->setImplement('Devrun\CmsModule\Forms\ILoginFormFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('form.registrationForm'))
            ->setImplement('Devrun\CmsModule\Forms\IRegistrationFormFactory')
            ->addSetup('setEmailSending', ['emailSending' => $config['emailSending']])
            ->addSetup('setEmailFrom', ['emailFrom' => $config['emailFrom']])
            ->setInject(true);

        $builder->addDefinition($this->prefix('form.forgottenPasswordForm'))
            ->setImplement('Devrun\CmsModule\Forms\IForgottenPasswordFormFactory')
            ->addSetup('setEmailSending', ['emailSending' => $config['emailSending']])
            ->addSetup('setEmailFrom', ['emailFrom' => $config['emailFrom']])
            ->setInject(true);

        $builder->addDefinition($this->prefix('form.profileFormFactory'))
            ->setImplement('Devrun\CmsModule\Forms\IProfileFormFactory')
            ->setInject(true);




        /*
         * controls
         */
        $builder->addDefinition($this->prefix('control.navigationPage'))
            ->setImplement('Devrun\CmsModule\Controls\INavigationPageControlFactory')
            ->setInject();

        $builder->addDefinition($this->prefix('control.environment'))
            ->setImplement('Devrun\CmsModule\Controls\IJSEnvironmentControl');


        $builder->addDefinition($this->prefix('control.pagesNestable'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\INestablePagesControl')
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.settingsControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\ISettingsControlFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.rawImagesControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\IRawImagesControlFactory')
            ->addSetup('setImageDir', [$builder->parameters['wwwDir'] . 'images' . DIRECTORY_SEPARATOR]);

        $builder->addDefinition($this->prefix('control.imageSettings'))
            ->setFactory('Devrun\CmsModule\Administration\Controls\PageImagesSettings')
            ->addSetup('setImages', [$config['content']['images']]);

        $builder->addDefinition($this->prefix('control.flashMessageControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IFlashMessageControlFactory');

        $builder->addDefinition($this->prefix('control.packageControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IPackageControlFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.themeControlFactory'))
            ->setImplement('Devrun\CmsModule\Controls\IThemeControlFactory')
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.modalActionControl'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\IModalActionControlFactory')
            ->addSetup('setEnable', [$config['content']['modalActions']])
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.treePageControlFactory'))
            ->setImplement('Devrun\CmsModule\Administration\Controls\ITreePagesControlFactory')
            ->addSetup('setFirstLevelVisible', [$config['administration']['treePageMenuFirstLevelVisible']])
            ->addTag('devrun.control')
            ->addTag('administration', [
                'category' => 'Content',
                'name'     => 'TreePagesControl',
                'priority' => 5
            ])
            ->setInject(true);

        $builder->addDefinition($this->prefix('control.carouselItemsControl'))
                ->setImplement('Devrun\CmsModule\Controls\ICarouselItemsControlFactory')
                ->setInject(true);



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
            ->addSetup('setDefaultDomain', [$config['website']['defaultDomain']]);


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
            ->setFactory('Devrun\CmsModule\Facades\DomainFacade', [$config['administration']['filenameDomain'], $config['website']['domainIPs']]);

        $builder->addDefinition($this->prefix('facade.settings'))
            ->setFactory('Devrun\CmsModule\Facades\SettingsFacade');


        $builder->addDefinition($this->prefix('facade.theme'))
            ->setFactory('Devrun\CmsModule\Facades\ThemeFacade', [$builder->parameters['modules'], $builder->parameters['wwwDir']]);

        $builder->addDefinition($this->prefix('facade.image'))
            ->setFactory('Devrun\CmsModule\Facades\ImageManageFacade')
            ->addSetup('setConfigEmptyImage', [$config['content']['emptyImage']])
            ->addTag(EventsExtension::TAG_SUBSCRIBER)
            ->setInject(true);

        /*
         * jobs
         */
        $builder->addDefinition($this->prefix('job.synchronizePage'))
            ->setFactory('Devrun\CmsModule\Facades\PageJobs\SynchronizePagesJob');



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
            ->setFactory('Devrun\CmsModule\Listeners\PageListener', [$config['options']['autoSyncPages']])
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

        $registerToLatte = function (Nette\DI\ServiceDefinition $def) {
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
        $config  = $this->getConfig($this->defaults);
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
     * @example https://github.com/nette/sandbox/blob/master/app/router/RouterFactory.php - createRouter()
     * @return \Nette\Application\IRouter
     */
    public function getRoutesDefinition()
    {
        $router   = new RouteList();
        $router[] = $adminRouter = new RouteList('Cms');

        $cmsDefaultLang = $defaultLocale = $availableLocales = 'cs';

        if ($translation = Nette\Environment::getService('translation.default')) {
            $availableLocalesArray = ($locales = $translation->getAvailableLocales())
                ? $locales
                : [$cmsDefaultLang];

            $availableLocales = implode('|', array_unique(preg_replace("/^(\w{2})_(.*)$/m", "$1", $availableLocalesArray)));

            if ($default = $translation->getDefaultLocale()) $defaultLocale = $default;
        }


        $pageRoute = Nette\Environment::getService('cms.pageRoute');

        $router[] = $pageRoute;


//        \Devrun\CmsModule\Routes\PageRouteFactory::createRouter(@cms.pageRoute)

        /*
         * @unComplete
         * must be at the end, afterCompile?
         */
        $frontRouter[] = new Route("[<locale={$defaultLocale} $availableLocales>/][<module>/]<presenter>/<action>[/<id>]", array(
            'module' => 'Front',
            'presenter' => array(
                Route::VALUE        => 'Homepage',
                Route::FILTER_TABLE => array(
                    'testovaci' => 'Test',
                ),
            ),
            'action'    => array(
                Route::VALUE        => 'default',
                Route::FILTER_TABLE => array(
                    'operace-ok' => 'operationSuccess',
                ),
            ),
            'id'        => null,
            'locale'    => [
                Route::FILTER_TABLE => [
                    'cz'  => 'cs',
                    'sk'  => 'sk',
                    'pl'  => 'pl',
                    'com' => 'en'
                ]]
        ));


        $adminRouter[] = new Route("[<module>-]admin[-package-<package>]/[<locale=$defaultLocale $availableLocales>/]<presenter>/<action>[/<id>]", array(
            'presenter' => 'Default',
            'action'    => 'default',

        ));

        return $router;
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