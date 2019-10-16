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

    protected function setUp()
    {
        parent::setUp();
        $this->initTranslateFile();
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
        $dir = $context->getParameters()['modules']['cms']['path'] . "/tests/resources/translations";

        $file = "$dir/test.cs.neon";

        $neon = new NeonAdapter();
        $data = $neon->load($file);

        $dump = $neon->dump($data);

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
