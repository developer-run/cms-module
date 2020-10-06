<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    PageFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Facades\PageJobs\PageJob;
use Devrun\CmsModule\Facades\PageJobs\SynchronizePagesJob;
use Devrun\CmsModule\InvalidArgumentException;
use Devrun\CmsModule\ModuleNotFoundException;
use Devrun\CmsModule\PresenterNotCreatedException;
use Devrun\CmsModule\Repositories\PageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\Module\BaseModule;
use Devrun\Module\IModule;
use Devrun\Module\ModuleFacade;
use Devrun\PhpGenerator\PhpFile;
use Devrun\Utils\FileTrait;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Caching\Storages\FileStorage;
use Nette\SmartObject;
use Nette\Utils\Validators;

class PageFacade
{
    use SmartObject;
    use FileTrait;

    private $modules;

    /** @var ModuleFacade */
    private $moduleFacade;

    /** @var SynchronizePagesJob */
    private $synchronizePagesJob;

    /** @var PageJob */
    private $pageJob;

    /** @var PageRepository */
    private $pageRepository;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var IStorage */
    private $pageCache;

    /**
     * PageFacade constructor.
     *
     * @param ModuleFacade         $moduleFacade
     * @param PageRepository       $pageRepository
     * @param IStorage|FileStorage $storage
     */
    public function __construct(ModuleFacade $moduleFacade, SynchronizePagesJob $synchronizePagesJob, PageJob $pageJob, PageRepository $pageRepository, IStorage $storage)
    {
        $this->moduleFacade        = $moduleFacade;
        $this->pageRepository      = $pageRepository;
        $this->pageJob             = $pageJob;
        $this->synchronizePagesJob = $synchronizePagesJob;

        $this->pageCache = new Cache($storage);
    }

    /**
     * @return SynchronizePagesJob
     */
    public function getSynchronizePagesJob(): SynchronizePagesJob
    {
        return $this->synchronizePagesJob;
    }

    /**
     * @return PageJob
     */
    public function getPageJob(): PageJob
    {
        return $this->pageJob;
    }


    /**
     * @return PageRepository
     */
    public function getPageRepository(): PageRepository
    {
        return $this->pageRepository;
    }

    /**
     * @return IModule[]
     */
    public function getModules(): array
    {
        if (null === $this->modules) {
            $this->modules = $this->moduleFacade->getModules();
        }

        return $this->modules;
    }

    /**
     * @param string $name
     *
     * @return IModule|NULL
     */
    public function getModule($name)
    {
        $modules = $this->getModules();
        return isset($modules[$name]) ? $modules[$name] : null;
    }

    /**
     * @param mixed $modules
     */
    public function setModule($modules)
    {
        $this->modules = $modules;
    }


    /**
     * @param $moduleName
     * @param $presenterName
     *
     * @return string
     */
    public function getPresenterClass($moduleName, $presenterName)
    {
        return ucfirst($moduleName) . "Module\\Presenters\\" . "{$presenterName}Presenter";
    }

    /**
     * @param $moduleName
     * @param $presenterName
     *
     * @return bool
     * @todo pro testy je vhodnější detekce pomocí existence souboru, v případě detekce pomocí class_exist je problém s mazáním třídy a následné detekci (třída je autoload v paměti)
     */
    public function presenterExists($moduleName, $presenterName)
    {
        if (PHP_SAPI != 'cli')
            return class_exists($presenterClass = $this->getPresenterClass($moduleName, $presenterName), true) ? $presenterClass : false;

        else return file_exists($this->getPresenterFile($moduleName, $presenterName))
            ? $this->getPresenterClass($moduleName, $presenterName)
            : false;
    }


    /**
     * @param $moduleName
     * @param $presenterName
     *
     * @return bool|string
     */
    public function getPresenterFile($moduleName, $presenterName)
    {
        if ($modulePresentersPath = $this->getModulePresentersPath($moduleName)) {
            return $modulePresentersPath . DIRECTORY_SEPARATOR . "{$presenterName}Presenter.php";
        }

        return false;
    }

    /**
     * @param $moduleName
     *
     * @return bool|string
     */
    public function getModulePresentersPath($moduleName)
    {
        if ($module = $this->getModule($moduleName)) {
            return $module->getPath() . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . ucfirst($moduleName) . "Module" . DIRECTORY_SEPARATOR . "Presenters";
        }

        return false;
    }

    /**
     * @param $moduleName
     * @param $presenterName
     *
     * @return bool|string
     */
    public function getPresenterTemplatePath($moduleName, $presenterName)
    {
        if ($presentersPath = $this->getModulePresentersPath($moduleName)) {
            return $presentersPath . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . ucfirst($presenterName);
        }

        return false;
    }



    /**
     * remove presenter class and all templates
     *
     * @param $moduleName
     * @param $presenterName
     */
    public function removePresenter($moduleName, $presenterName)
    {
        /** @var BaseModule[] $modules */
        $modules = $this->moduleFacade->getModules();
        if (isset($modules[$moduleName])) {

            $path = $modules[$moduleName]->getPath();

            if (is_dir($presentersDirName = $path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . ucfirst($moduleName) . "Module" . DIRECTORY_SEPARATOR . "Presenters")) {

                if ($presenterClass = $this->presenterExists($moduleName, $presenterName)) {
                    // remove class
                    $reflectionClass = new \ReflectionClass($presenterClass);
                    @unlink($reflectionClass->getFileName());
                }

                if (is_dir($presenterTemplates = $presentersDirName . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . ucfirst($presenterName))) {
                    // remove directory
                    self::rmdir($presenterTemplates, true);
                }
            }
        }
    }


    /**
     * create public presenter
     *
     * @param $presenterName
     *
     * @return bool|PhpFile
     */
    public function createPresenter($moduleName, $presenterName)
    {
        $modules = $this->moduleFacade->getModules();
        if (!isset($modules[$moduleName])) {
            throw new ModuleNotFoundException("Module $moduleName not found");
        }

        /** @var BaseModule $module */
        $module = $modules[$moduleName];

        $className = ucfirst($presenterName) . "Presenter";
        $namespace = ucfirst($moduleName) . "Module\\Presenters";

        if (!$this->presenterExists($moduleName, $presenterName)) {
            $path = $module->getPath();

            if (!is_dir($dirName = $path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . ucfirst($moduleName) . "Module" . DIRECTORY_SEPARATOR . "Presenters")) {
                umask(0000);
                mkdir($dirName, 0644, TRUE);
            }

            $fileName = $dirName . DIRECTORY_SEPARATOR . "{$presenterName}Presenter.php";

            $phpFile = new PhpFile();

            $namespace = $phpFile->addNamespace($namespace);
            $namespace
                ->addUse('Nette')
                ->addUse('Devrun');

            $class = $namespace->addClass($className)
                ->addComment("Class $className")
                ->setExtends('Devrun\Application\UI\Presenter\BasePresenter');

            file_put_contents($fileName, (string)$phpFile);
            return $phpFile;
        }

        return false;
    }


    /**
     * @param        $moduleName
     * @param        $presenterName
     * @param        $pageName
     * @param string $method [Action|Render]
     */
    public function createPage($moduleName, $presenterName, $pageName, $method = "Render")
    {
        if (!Validators::isInRange($method, ['Action', 'Render'])) {
            throw new InvalidArgumentException('Page must be type "Action|Render"');
        }

        if (!$this->presenterExists($moduleName, $presenterName)) {
            if (!$phpFile = $this->createPresenter($moduleName, $presenterName)) {
                throw new PresenterNotCreatedException("presenter $presenterName in module $moduleName not created");
            }

            $presenterClass = $phpFile->getClassType($presenterClassName = $this->getPresenterClass($moduleName, $presenterName));

        } else {
            $phpFile        = new PhpFile();
            $presenterClass = $phpFile->fromClass($presenterClassName = $this->getPresenterClass($moduleName, $presenterName));
        }

        $method = $presenterClass->addMethod(lcfirst($method) . ucfirst($pageName));

        $templateContent = "{block content}\nempty page\n\n\n{/block}";

        $presenterFile         = $this->getPresenterFile($moduleName, $presenterName);
        $presenterTemplatePath = $this->getPresenterTemplatePath($moduleName, $presenterName);

        if (!is_dir($presenterTemplatePath)) {
            umask(0000);
            mkdir($presenterTemplatePath, 0755, TRUE);
        }

        $templateFilename = $presenterTemplatePath . DIRECTORY_SEPARATOR . lcfirst($pageName) . ".latte";

        file_put_contents($templateFilename, $templateContent);
        file_put_contents($presenterFile, (string)$phpFile);
    }


    public function createLayout($moduleName, $presenterName)
    {
        $presenterTemplatePath = $this->getPresenterTemplatePath($moduleName, $presenterName);

        $templateContent = <<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    {include content}
</body>
</html>
EOT;

        if (!is_dir($presenterTemplatePath)) {
            umask(0000);
            mkdir($presenterTemplatePath, 0755, TRUE);
        }

        $layoutFilename = $presenterTemplatePath . DIRECTORY_SEPARATOR . "@layout.latte";
        file_put_contents($layoutFilename, $templateContent);
    }


    /**
     * @param $moduleName
     * @param $presenterName
     * @param $pageName
     */
    public function removePage($moduleName, $presenterName, $pageName)
    {
        if ($this->presenterExists($moduleName, $presenterName)) {
            $phpFile = new PhpFile();
            $presenterClass = $phpFile->fromClass($presenterClassName = $this->getPresenterClass($moduleName, $presenterName));

            $methods = $presenterClass->getMethods();

            unset($methods['action' . ucfirst($pageName)]);
            unset($methods['render' . ucfirst($pageName)]);

            $presenterFile = $this->getPresenterFile($moduleName, $presenterName);
            $presenterClass->setMethods($methods);

            $presenterTemplatePath = $this->getPresenterTemplatePath($moduleName, $presenterName);
            $templateFilename = $presenterTemplatePath . DIRECTORY_SEPARATOR . lcfirst($pageName) . ".latte";

            if (file_exists($templateFilename)) {
                @unlink($templateFilename);

                if (self::isDirEmpty($presenterTemplatePath)) {
                    self::rmdir($presenterTemplatePath);
                }
            }

            file_put_contents($presenterFile, (string)$phpFile);
        }
    }


    public function getPresenterPhpGenerator($moduleName, $presenterName)
    {
        $phpFile = new PhpFile();
//        dump(__FUNCTION__ . " " . spl_object_hash($phpFile));

        if ($this->presenterExists($moduleName, $presenterName)) {
            $phpFile->fromClass($presenterClassName = $this->getPresenterClass($moduleName, $presenterName));
        }

        dump($phpFile);


//        $q = $phpFile->getClassType($presenterClassName)->methods;
//        dump($q);

        return $phpFile;
    }

}