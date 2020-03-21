<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ThemeFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;


use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\InvalidArgumentException;
use Devrun\CmsModule\InvalidStateException;
use Devrun\FileNotFoundException;
use Kdyby\Monolog\Logger;
use Nette\Http\FileUpload;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class ThemeFacade
{
    const DEFAULT_NAME = 'default';
    const LAYOUT_PATH = '/resources/layouts';
    const THEME_NAME = 'theme-default';
    const URI_IMAGES = '../../../images';


    private $options = array('compress' => false);

    /** @var string */
    private $lessFile;

    /** @var string */
    private $cssFilename;

    /** @var Logger */
    private $logger;

    /** @var bool */
    private $generateImgVersion = true;

    /** @var string DI setting */
    private $wwwDir;

    /** @var array DI setting */
    private $modulesInfo = [];


    /** @var PackageEntity */
    private $packageEntity;

    /** @var string basePath theme,  for example ../../images/my-module/theme-default */
    private $uri = self::URI_IMAGES;

    /** @var string for example theme-default */
    private $themeName = self::THEME_NAME;

    /** @var string for example %wwwDir%/app/modules/my-module/resources/layouts/default-theme */
    private $themePath;

    /** @var string result from generateCss */
    private $css = '';


    /**
     * ThemeFacade constructor.
     *
     * @param array $modulesInfo
     * @param $wwwDir
     * @param Logger $logger
     */
    public function __construct(array $modulesInfo, $wwwDir, Logger $logger)
    {
        $this->wwwDir      = $wwwDir;
        $this->modulesInfo = $modulesInfo;
        $this->logger      = $logger;
    }


    /**
     * @param PackageEntity $packageEntity
     *
     * @return $this
     */
    public function settingsFromPackage(PackageEntity $packageEntity)
    {
        $this
            ->setThemeName($packageEntity)
            ->setThemePath($packageEntity->getModule())
            ->setUri($packageEntity->getModule())
            ->setCssFilename($packageEntity->getModule());

        $this->packageEntity = $packageEntity;
        return $this;
    }


    /**
     * @return $this
     */
    public function generateThemeCss()
    {
        $package = $this->getPackageEntity();
        $variableSettings = $this->getVariableSettings();
        $themeVariables = $this->getThemeVariables();

        $lessVariables = $this->modifyVariablesForLess($themeVariables);
        $lessVariables += ['themeName' => $this->getThemeName()];

        $css = $this->generateCss($lessVariables);
        $this->css = $css;

        return $this;
    }


    /**
     * copy image directory source to destination
     *
     * @param PackageEntity $sourcePackage
     * @param PackageEntity $destinationPackage
     */
    public function copyPackageSourcesToDestinationSources(PackageEntity $sourcePackage, PackageEntity $destinationPackage)
    {
        $sourcePath = $this->setThemeName($sourcePackage)->getDestinationImagePath($sourcePackage->getModule());
        $destinationPath = $this->setThemeName($destinationPackage)->getDestinationImagePath($destinationPackage->getModule());

        if (!is_dir($sourcePath)) {
            mkdir($sourcePath, 0775);
        }

        FileSystem::copy($sourcePath, $destinationPath);
    }


    /**
     * @return $this
     */
    public function save()
    {
        if ($this->css) {
            $cssFilename = $this->getCssFileName();
            file_put_contents($cssFilename, $this->css);
        }
        return $this;
    }


    /**
     * @return array
     */
    public function getThemeVariables()
    {
        $packageEntity = $this->getPackageEntity();
        return $packageEntity ? $packageEntity->getThemeVariables() : [];
    }


    /**
     * @param $moduleName
     *
     * @return $this
     */
    private function setUri($moduleName)
    {
        $themeName = $this->getThemeName();
        $this->uri = self::URI_IMAGES . "/$moduleName/$themeName";
        return $this;
    }

    private function getUri()
    {
        return $this->uri;
    }



    private function getLessFileName()
    {
        $filename = $this->getThemePath() . "/theme.less";
        if (!file_exists($filename)) {
            $content = "";
            file_put_contents($filename, $content);
        }

        return $filename;
    }


    /**
     * @param bool $force
     * @return string
     */
    private function getCustomLessFileName($force = false)
    {
        $moduleName = $this->getModuleName();
        $themeName  = $this->getThemeName();
        $customName = "custom-$themeName.less";
        $filename   = $this->wwwDir . "/less/{$moduleName}/$customName";

        if ($force && !file_exists($filename)) {
            $content = "";
            file_put_contents($filename, $content);
        }

        return $filename;
    }


    /**
     * @return false|string
     */
    public function isCustomLess()
    {
        return file_exists($this->getCustomLessFileName(false));
    }

    /**
     * @return false|string
     */
    public function loadCustomLess()
    {
        return file_get_contents($this->getCustomLessFileName(true));
    }


    /**
     * @param $customLess
     */
    public function saveCustomLess($customLess)
    {
        file_put_contents($this->getCustomLessFileName(true), $customLess);
    }


    private function getNeonFileName()
    {
        $filename = $this->getThemePath() . "/settings.neon";
        if (!file_exists($filename)) {
            $content = "";
            file_put_contents($filename, $content);
        }

        return $filename;
    }

    public function getVariableSettings()
    {
        $settings = file_get_contents($this->getNeonFileName());

        $neon = new Neon();
        return $neon::decode($settings);
    }


    private function getCssFilename()
    {
        if (!$this->cssFilename) {
            throw new InvalidArgumentException("set cssFilename first");
        }

        return $this->cssFilename;
    }


    /**
     * @param $moduleName
     *
     * @return $this
     */
    private function setCssFilename($moduleName)
    {
        $themeName = $this->getThemeName();

        if (!is_dir($dir = $this->wwwDir . "/css/{$moduleName}/themes")) {
            if (!is_writable($dir)) {
                throw new FileNotFoundException("{$dir} is not writable!");
            }

            @mkdir($dir, 0775, true);
        }

        $this->cssFilename = "$dir/$themeName.css";
        return $this;
    }


    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }






    private function getDestinationImagePath($moduleName)
    {
        $themeName = $this->getThemeName();

        if (!is_dir($imagesPath = $this->wwwDir . "/images/{$moduleName}/$themeName")) {
            if (!is_writable($this->wwwDir . "/images/{$moduleName}")) {
                $this->logger->addError($this->wwwDir . "/images/{$moduleName}", []);
                throw new FileNotFoundException("{$imagesPath} is not writable!");
            }

            @mkdir($imagesPath, 0775, true);
        }

        return $imagesPath;
    }




    /**
     * Returns theme path
     * @example %wwwDir%/app/modules/my-module/resources/layouts/default-theme
     *
     * @return string
     */
    private function getThemePath()
    {
        if (!$this->themePath) {
            throw new InvalidArgumentException("set ThemePath first");
        }
        return $this->themePath;
    }

    /**
     * set theme path
     * @example %wwwDir%/app/modules/my-module/resources/layouts/default-theme
     *
     * @param string $moduleName
     *
     * @return $this
     */
    private function setThemePath($moduleName)
    {
        $layoutPath = $this->modulesInfo[$moduleName]['path'] . self::LAYOUT_PATH;

        if (!is_dir($themePath = $layoutPath . DIRECTORY_SEPARATOR . self::THEME_NAME)) {
            if (!is_writable($layoutPath)) {
                throw new FileNotFoundException("{$layoutPath} is not writable!");
            }

            @mkdir($themePath, 0775, true);
        }

        $this->themePath = $themePath;
        return $this;
    }


    /**
     * @param $values
     *
     * @return string
     */
    private function generateCss($values)
    {
        $parser = new \Less_Parser($this->options);
        $lessFile = $this->getLessFileName();

        $parser->parseFile($lessFile, $this->getUri());
        // $variables = $parser->getVariables();
        $parser->ModifyVars($values);
        // $variables = $parser->getVariables();
        return $parser->getCss();
    }





    private function modifyVariablesForLess($values)
    {
        $result   = [];
        $settings = $this->getVariableSettings();

        foreach ($values as $key => $value) {
            if (isset($settings[$key])) {
                $result[$key] = $value;
                // update name, for example "\"image.pnp\""
                if (isset($settings[$key]['input']) && $settings[$key]['input'] == 'upload') {
                    $v = $this->generateImgVersion ? "?v=" . time() : null;
                    $imgName = "\"" . trim($result[$key]) . $v . "\"";
                    $result[$key] = $imgName;
                }
                // update item units, for example  15px
                if (isset($settings[$key]['units'])) {
                    $result[$key] = $result[$key] . $settings[$key]['units'];
                }
            }
        }

        return $result;
    }


    /**
     * @param       $values
     *
     * @return array
     */
    public function modifyAndSendVariablesFromForm($values, PackageEntity $packageEntity)
    {
        $result = [];
        $themeVariables = $this->getThemeVariables();
        $imagePath = $this->getDestinationImagePath($packageEntity->getModule());


        if (!$this->themePath) {
            $this->setThemePath($packageEntity->getModule());
        }
        if (!$this->themeName) {
            $this->setThemeName($packageEntity);
        }


        // mask variables settings for form and less
        $settings = $this->getVariableSettings();

        if ($values->custom) {
            $this->saveCustomLess($values->custom);
        }

        foreach ($values as $key => $value) {
            if (isset($settings[$key])) {
                if ($value instanceof FileUpload) {

                    if ($value->isOk()) {
                        $name          = Strings::webalize($value->getName(), '._');
                        $normalizeName = $imagePath . DIRECTORY_SEPARATOR . $name;

                        if (isset($themeVariables[$key])) {
                            $existFile = $imagePath . DIRECTORY_SEPARATOR . $themeVariables[$key];

                            if (file_exists($existFile)) {
                                unlink($existFile);
                            }
                        }

                        $value->move($normalizeName);
                        $result[$key] = $name;
                    }

                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }




    /**
     * @param PackageEntity $package
     *
     * @return $this
     */
    public function setThemeName(PackageEntity $package = null)
    {
        $this->themeName = ($package && $package->getName())
            ? "theme-{$package}"
            : "theme-" . self::DEFAULT_NAME;

        return $this;
    }


    /**
     * @return string
     */
    public function getThemeName()
    {
        return $this->themeName;
    }



    /**
     * @return PackageEntity
     */
    public function getPackageEntity(): PackageEntity
    {
        if (!$packageEntity = $this->packageEntity) {
            throw new InvalidStateException("settingsFromPackage() first");
        }

        return $this->packageEntity;
    }


    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->getPackageEntity()->getModule();
    }


}