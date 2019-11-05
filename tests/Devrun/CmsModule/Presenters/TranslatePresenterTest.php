<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    TranslatePresenterTest.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\Tests\Presenter;
use Nette\DI\Config\Adapters\NeonAdapter;

/**
 * Class TranslatePresenterTest
 *
 * @backupGlobals disabled
 * @package       Devrun\CmsModule\Presenters
 */
class TranslatePresenterTest extends Presenter
{

    public static $migrations = true;

    protected function setUp()
    {
        parent::setUp();
        $this->initTranslateFile();
    }


    public function testNoLoggedIn()
    {
        $this->init('Cms:Translate');

        $params = array(
            'content'     => 'Obsah česky',
            'domain'      => "test",
            'translateId' => "title1",
            'action'      => 'update',
        );

        $request   = $this->getRequest($params);
        $response  = $this->getResponse($request);
        $presenter = $this->presenter;

        $this->assertInstanceOf('Nette\Application\Responses\RedirectResponse', $response);
        $this->assertEquals($this->uriCheck($presenter->link('//Login:')), $this->uriCheck($response->getUrl()));
    }


    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testTwoKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content' => 'Obsah česky',
            'domain' => "test",
            'translateId' => "title1",
            'action' => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('title1', $data);
    }


    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testThreeKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content'     => 'Obsah česky',
            'domain' => "test",
            'translateId' => "homepage.title1",
            'action'      => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('homepage', $data);
        $this->assertArrayHasKey('title1', $data['homepage']);
    }


    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testFourKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content'     => 'scheduleAsyncSearch',
            'domain' => "test",
            'translateId' => "homepage.demoComponent.title1",
            'action'      => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('homepage', $data);
        $this->assertArrayHasKey('demoComponent', $data['homepage']);
        $this->assertArrayHasKey('title1', $data['homepage']['demoComponent']);
    }



    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testFifthKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content'     => 'scheduleAsyncSearch',
            'domain' => "test",
            'translateId' => "homepage.demoComponent.position.title1",
            'action'      => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('homepage', $data);
        $this->assertArrayHasKey('demoComponent', $data['homepage']);
        $this->assertArrayHasKey('position', $data['homepage']['demoComponent']);
        $this->assertArrayHasKey('title1', $data['homepage']['demoComponent']['position']);
    }



    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testSixthKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content'     => 'scheduleAsyncSearch',
            'domain' => "test",
            'translateId' => "homepage.demoComponent.position.two.title1",
            'action'      => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('homepage', $data);
        $this->assertArrayHasKey('demoComponent', $data['homepage']);
        $this->assertArrayHasKey('position', $data['homepage']['demoComponent']);
        $this->assertArrayHasKey('two', $data['homepage']['demoComponent']['position']);
        $this->assertArrayHasKey('title1', $data['homepage']['demoComponent']['position']['two']);
    }



    /**
     * @throws \Nette\Utils\AssertionException
     */
    public function testSeventhKeys()
    {
        $this->sendLoginForm();

        $params = array(
            'content'     => 'scheduleAsyncSearch',
            'domain' => "test",
            'translateId' => "homepage.demoComponent.position.two.seventh.title1",
            'action'      => 'update',
        );

        $this->init('Cms:Translate');

        $request = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
        $this->assertTrue($response->payload->translate);

        $data = $this->getTranslateFile();

        $this->assertArrayHasKey('homepage', $data);
        $this->assertArrayHasKey('demoComponent', $data['homepage']);
        $this->assertArrayHasKey('position', $data['homepage']['demoComponent']);
        $this->assertArrayHasKey('two', $data['homepage']['demoComponent']['position']);
        $this->assertArrayHasKey('seventh', $data['homepage']['demoComponent']['position']['two']);
        $this->assertArrayHasKey('title1', $data['homepage']['demoComponent']['position']['two']['seventh']);
    }



    protected function getTranslateFile()
    {
        $context = $this->getContainer();
        $dir = $context->getParameters()['modules']['cms']['path'] . "tests/resources/translations";

        $file = "$dir/test.cs.neon";

        $neon = new NeonAdapter();
        $data = $neon->load($file);

        return $data;
    }


    protected function initTranslateFile()
    {
        $context = $this->getContainer();
        $dir     = $context->getParameters()['modules']['cms']['path'] . "tests/resources/translations";
        $file    = "$dir/test.cs.neon";

        $neon = new NeonAdapter();

        $array = [
            'page'  => 'Strana',
            'prvni' => 'První pozice',
            'druha' => [
                'strana' => 'Stránka',
                'treti'  => [
                    'vrstva' => 'Vrstva',
                    'crvrta' => [
                        'dalsi'    => 'Vrstev',
                        'posledni' => [
                            'pata' => 'Pátá vrstva',
                        ]
                    ],
                ],
            ],
        ];

        file_put_contents($file, $neon->dump($array));
    }


}
