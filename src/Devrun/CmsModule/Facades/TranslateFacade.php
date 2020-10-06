<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    TranslateFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades;

use Devrun\CmsModule\Facades\Translate\ListArray;
use Devrun\CmsModule\TranslateException;
use Devrun\Utils\Arrays;
use Kdyby\Monolog\Logger;
use Kdyby\Translation\Translator;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Symfony\Component\Config\Resource\FileResource;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class TranslateFacade
{
    use SmartObject;

    /** @var Translator */
    private $translator;

    /** @var Logger */
    private $logger;

    /** @var array */
    private $domains;


    /**
     * TranslateFacade constructor.
     *
     * @param Translator $translator
     * @param Logger     $logger
     */
    public function __construct(Translator $translator, Logger $logger)
    {
        $this->translator = $translator;
        $this->logger     = $logger;
    }


    /**
     * @return array
     */
    public function getDefaultDomains()
    {
        return array_keys($this->getDefaultDomainsWithMessages());
    }


    /**
     * @return array
     */
    public function getDefaultDomainsWithMessages()
    {
        if (null === $this->domains) {
            $defaultLocale = $this->translator->getDefaultLocale();

            $locale = $this->translator->getLocale();

            $this->translator->setLocale($defaultLocale);




            $catalogue      = $this->translator->getCatalogue();
//            $firstCatalog   = $catalogue->getFallbackCatalogue();
//            $defaultCatalog = $firstCatalog->getFallbackCatalogue();


//            dump($catalogue->getFallbackCatalogue()->getDomains());
//            dump($catalogue->getFallbackCatalogue()->all());
//            die;


//            $this->domains = array_merge($catalogue->all(), $catalogue->getFallbackCatalogue()->all());
            $this->domains = $catalogue->all() + $catalogue->getFallbackCatalogue()->all();

            $this->translator->setLocale($locale);


//            dump($this->domains);
//            die;

//            dump($catalogue);
//            dump($catalogue->getDomains());
//
//            dump($firstCatalog);
//            dump($firstCatalog->getDomains());
//
//            dump($defaultCatalog);
//            dump($catalogue->all());



//            die;
//            $this->domains = $catalogue->all();
//            $this->domains = $defaultCatalog ? $defaultCatalog->all() : $firstCatalog->all();
        }

        return $this->domains;
    }


    /**
     * @param string $domain
     *
     * @return array
     */
    public function getTranslateData($domain = 'site')
    {
        $result = [];
//        dump($domain);

        if ($defaultDomains = $this->getDefaultDomainsWithMessages()) {

//            dump($defaultDomains);
//            die;

            if (isset($defaultDomains[$domain])) {
                $catalogue = $this->translator->getCatalogue()->all($domain);  // actual language
//                $thisCatalog = $catalogue->getFallbackCatalogue();
//                $thisMessages = $thisCatalog->all();

//                dump($catalogue);
//                dump($thisCatalog);
//                dump($thisMessages);
//                die;

                foreach ($defaultDomains[$domain] as $id => $defaultMessage) {
                    $result[] = [
                        'id' => $id,
                        'defaultValue' => $defaultMessage,
                        'localeValue' => $catalogue[$id] ?? "?",
                    ];
                }
            }
        }

//        dump($result);
//        die;


        return $result;
    }


    /**
     * find locale neon file for domain   (actual select location)
     *
     * @param string $domain
     * @param bool $autoCreateNewLocale
     * @return string|null
     */
    private function findNeonFile(string $domain, bool $autoCreateNewLocale = true): ?string
    {
        $result    = null;
        $locale    = $this->translator->getLocale();
        $default   = $this->translator->getDefaultLocale();
        $catalogue = $this->translator->getCatalogue();
        $resources = $catalogue->getResources();

        /** @var $fileResource FileResource */
        foreach ($resources as $fileResource) {
            if (preg_match("%^$domain\.$locale\w*\.neon$%i", basename($fileResource))) {
                $result = $fileResource->getResource();
                break;
            }
        }


        /*
         * if resource not found, create empty file resource
         */
        if ($autoCreateNewLocale && null === $result) {
            foreach ($resources as $fileResource) {
                if (preg_match("%^$domain\.($default\w*)\.neon$%i", basename($fileResource), $matches)) {
                    $baseNewResource = "$domain.$locale.neon";
                    $file = pathinfo($fileResource, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $baseNewResource;

                    if (!file_exists($file)) {
                        file_put_contents($file, '');
                    }

                    $result = $file;
                    break;
                    //                    $findDefaultLocale = $matches[1]; // cs | cs_CZ
                    //                    $newLocale = preg_replace("%^$default%", $locale, $findDefaultLocale);
                    //
                    //                    /*
                    //                     * search new locale from available locale list
                    //                     * nejdříve nalezneme přesnou verzi [sk_SK]
                    //                     */
                    //                    if (($index = array_search($newLocale, $this->translator->getAvailableLocales())) !== false) {
                    //                        $newLocale = $this->translator->getAvailableLocales()[$index];
                    //                    }
                    //
                    //                    $baseNewResource = "$domain.$matches[1].neon";
                    //                    $baseNewResource = "$domain.$locale.neon";
                    //                    break;
                }
            }
        }

        return $result;
    }


    /**
     * get name of neon filename for domain
     *
     * @param $domain
     *
     * @example site.cs_CZ.neon
     * @return string
     */
    private function getNeonFileName($domain)
    {
        $catalogue     = $this->translator->getCatalogue();
        $localeCatalog = $catalogue->getFallbackCatalogue();

        return "$domain.{$localeCatalog->getLocale()}.neon";
    }

    /**
     * @param $content
     */
    private function filterEditHtml(&$content)
    {
        $crawler = HtmlPageCrawler::create($content);
        $filter  = $crawler->filter('div.translate-box');
        if ($filter->count() > 0) {
            $content = $filter->getInnerHtml();
        }
    }


    /**
     * update domain translate by id
     *
     * @param $domain
     * @param $id
     * @param $content
     *
     * @return bool
     */
    public function updateTranslate($domain, $id, $content)
    {
        $result = false;

//        $catalogue     = $this->translator->getCatalogue();
//        $localeCatalog = $catalogue->getFallbackCatalogue();

        if ($localeFile = $this->findNeonFile($domain)) {

            $this->filterEditHtml($content);

            $neon   = new NeonAdapter();
            $data   = $neon->load($localeFile);
            $ids    = explode('.', $id);
            $data   = Arrays::setByArrayKeys($data, $ids, html_entity_decode($content));
            $output = $neon->dump($data);

            if (@file_put_contents($localeFile, $output)) {
                $this->logger->addDebug(__FUNCTION__ . " domain [$domain] id [$id]");
                $result = true;

            } else {
                $this->logger->addWarning(__FUNCTION__ . " cant write to [$localeFile]!");
                throw new TranslateException(__FUNCTION__ . " cant write to [$localeFile]!");
            }

        } else {
//            dump("nenalezen $localeFile");
//            die("ASS");

            $fileName = $this->getNeonFileName($domain);
            $this->logger->addWarning(__FUNCTION__ . " [$fileName] not found!");
            throw new TranslateException(__FUNCTION__ . " [$fileName] not found!");
        }

        return $result;
    }


    /**
     * create key sorting list of translate
     *
     * @param array $data
     *
     * @return array
     */
    public function createTranslateListFromArray(array $data, $domain = 'site', $separator = '.')
    {
        return (new ListArray($data, $domain, $separator))->getTranslateList();
    }





}