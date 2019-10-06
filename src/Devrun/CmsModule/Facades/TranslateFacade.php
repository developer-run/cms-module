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
use Tracy\Debugger;

class TranslateFacade
{
    use SmartObject;

    /** @var Translator */
    private $translator;

    /** @var Logger */
    private $logger;


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
        $catalogue      = $this->translator->getCatalogue();
        $firstCatalog   = $catalogue->getFallbackCatalogue();
        $defaultCatalog = $firstCatalog->getFallbackCatalogue();

        return $defaultCatalog ? $defaultCatalog->all() : $firstCatalog->all();
    }


    /**
     * @param string $domain
     *
     * @return array
     */
    public function getTranslateData($domain = 'site')
    {
        $result = [];

        if ($defaultDomains = $this->getDefaultDomainsWithMessages()) {
            if (isset($defaultDomains[$domain])) {
                $catalogue = $this->translator->getCatalogue();
                $thisCatalog = $catalogue->getFallbackCatalogue();
                $thisMessages = $thisCatalog->all();
                
                foreach ($defaultDomains[$domain] as $id => $defaultDomain) {
                    $result[] = [
                        'id' => $id,
                        'defaultValue' => $defaultDomain,
                        'localeValue' => isset($thisMessages[$domain][$id]) ? $thisMessages[$domain][$id] : null,
                    ];
                }
            }
        }

        return $result;
    }


    /**
     * find locale neon file for domain   (actual select location)
     *
     * @param $domain
     *
     * @return bool
     */
    private function findNeonFile($domain)
    {
        $result        = false;
        $catalogue     = $this->translator->getCatalogue();
        $localeCatalog = $catalogue->getResources();

        /** @var $fileResource FileResource */
        foreach ($localeCatalog as $fileResource) {
            $resource = Strings::before(basename($fileResource), '.');
            if ($resource == $domain) {
                $result = $fileResource->getResource();
                break;
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