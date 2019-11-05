<?php

$loader = require __DIR__ . '/../../../../vendor/autoload.php';

$configurator = new \Devrun\Config\Configurator(dirname(__DIR__) . '/../../../app', $debugMode = null, $loader);

/*
 * clear logs
 */
$sandboxParameters = $configurator->getSandboxParameters();
$logDir = $sandboxParameters['logDir'];
\Devrun\Utils\FileTrait::eraseDirFromFiles($logDir, ['*.log', '*.html']);

/*
 * clear cache
 */
$sandboxParameters = $configurator->getSandboxParameters();
$tempDir = $sandboxParameters['tempDir'];
\Devrun\Utils\FileTrait::purge("$tempDir/cache");

error_reporting(~E_USER_DEPRECATED); // note ~ before E_USER_DEPRECATED

$robotLoader = $configurator->createRobotLoader();
$robotLoader
    ->addDirectory(dirname(__DIR__) . '/src')
    ->addDirectory(dirname(__DIR__) . '/../front-module/src')
    ->ignoreDirs .= ', templates, test, resources';
$robotLoader->register();

$environment = 'test';

$configurator->addConfig(__DIR__ . '/../../../../app/config/config.neon');
$configurator->addConfig(__DIR__ . "/../../../../app/config/config.$environment.neon");

$container = $configurator->createContainer();

return $container;


