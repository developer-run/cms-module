<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017

*
*@file    PageListenerTest.phpt
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */
namespace Devrun\CmsModule\Listeners;

use Nette\Application\Request;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . "/../../../../../../../tests/Devrun/bootstrapNetteTester.php";

/**
 * @testCase
 */
class PageListenerTest  extends TestCase
{

    /** @var Container */
    private $container;

    /**
     * PageListenerTest constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;


    }


    public function testOne()
    {
        Assert::true(true, 'Mělo by');
    }

    public function testTwo()
    {
//        Assert::true(false, 'Popisek nespoc');
    }


    public function testThree()
    {
        $presenterName = 'Front:Test';

        $params = array(
            'action' => 'default',
        );

        $method = "POST";

        $post = [];
        $files = [];

        $request = new Request($presenterName, $method, $params, $post, $files);

        $presenterFactory            = $this->container->getByType('Nette\Application\IPresenterFactory');
        /** @var \TestPresenter $presenter */
        $presenter = $presenterFactory->createPresenter($presenterName);
        $presenter->autoCanonicalize = FALSE;


//        dump($presenter);
//        dump($request);

        $response = $presenter->run($request);

        Assert::type('Nette\Application\Responses\TextResponse', $response);

        $html = (string)$response->getSource();

        dump($html);
        die(1);




        Assert::true(true);

//        dump($this);

//        die("AAA");

    }



}

(new PageListenerTest($container))->run();


//(new PageListenerTest($container))->run();

