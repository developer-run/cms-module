<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//$container = require __DIR__ . '/../migrations/run.php';
$container = require __DIR__ . '/../../bootstrap.php';
$container->getByType('Nette\Application\Application')->run();
