<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    Validate.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\Domain;

use Devrun\Utils\Url;
use Kdyby\Monolog\Logger;
use Nette\Utils\Validators;

class Validator
{

    /** @var Logger */
    private $logger;

    /** @var string */
    private $filenameDomain;

    /** @var array */
    private $domainIPs = [];


    /**
     * PackageDomain constructor.
     *
     * @param string $filenameDomain
     * @param array  $domainIPs
     * @param Logger $logger
     */
    public function __construct(string $filenameDomain, array $domainIPs, Logger $logger)
    {
        $this->logger = $logger;
        $this->domainIPs = $domainIPs;
        $this->filenameDomain = $filenameDomain;
    }


    public function isValidDomain($domain)
    {
        $ip = Url::getDomainIP($domain);

        //$valid = Url::isValidDomain($domain);
        $valid = Validators::isUri($domain);

        return in_array($ip, $this->domainIPs);
    }


    public function addValidDomain($domain)
    {
        $file = $this->filenameDomain;

        if (file_exists($file)) {

        } else {
            file_put_contents($file, "");
        }

        $lines = [];

        $fh = fopen($file,'r');
        while ($line = fgets($fh)) {
            $lines[] = trim($line);
        }
        fclose($fh);

        sort($lines);
        $values = array_flip(array_unique($lines));

        $values[$domain] = true;
        unset($values[""]);

        $values = array_keys($values);

        $fp = fopen($file, 'w');
        foreach ($values as $value) {
            fwrite($fp, $value . PHP_EOL);
        }
        fclose($fp);

        $this->logger->addDebug(__FUNCTION__ . " domain [$domain] added");
    }


    public function setValidDomains(array $domains)
    {
        $file = $this->filenameDomain;

        if (file_exists($file)) {

        } else {
            file_put_contents($file, "");
        }

        sort($domains);
        $values = array_flip(array_unique($domains));
        $values = array_keys($values);

        $fp = fopen($file, 'w');
        foreach ($values as $value) {
            fwrite($fp, $value . PHP_EOL);
        }
        fclose($fp);
    }


}