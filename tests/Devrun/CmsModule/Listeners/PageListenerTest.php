<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    PageListenerTest.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Listeners;

use Devrun\Tests\Presenter;
use Tracy\Debugger;

/**
 * Class PageListenerTest
 *
 * @backupGlobals disabled
 * @package       Devrun\CmsModule\Listeners
 */
class PageListenerTest extends Presenter
{
    /** @var \Devrun\CmsModule\Facades\PageFacade @inject */
    public $pageFacade;

    /** @var \Devrun\Module\ModuleFacade @inject */
    public $moduleFacade;



    /**
     *
     */
    public function testCreatePresenter()
    {
        $this->sendLoginForm();

        // nalezneme seznam public modulů
        $this->assertNotEmpty($publicModules = $this->moduleFacade->findUnSyncedPublicStaticPages(), "nenalezen public modul");

        $testPresenter = "Test";
        $publicModule  = array_keys($publicModules)[0];


        if ($this->pageFacade->presenterExists($publicModule, $testPresenter)) {
            $this->pageFacade->removePresenter($publicModule, $testPresenter);
        }


        // vytvoříme presenter v prvním public modulu
        $this->assertNotEmpty($phpFile = $this->pageFacade->createPresenter($publicModule, $testPresenter), 'presenter se nepodařilo vytvořit');

        // vytvoříme prázdný layout pro testy
        $this->pageFacade->addLayout($publicModule, $testPresenter);


//        return;

        /*
                dump($phpFile);
                die();


                $params = array(
                    'content'     => 'Obsah česky',
                    'translateId' => "test.title1",
                    'action'      => 'update',
                );

                $this->init('Cms:Translate');

                $request = $this->getRequest($params);
                $response = $this->getResponse($request);

                $this->assertInstanceOf('Nette\Application\Responses\JsonResponse', $response);
                $this->assertTrue($response->payload->translate);

                $data = $this->getTranslateFile();

                $this->assertArrayHasKey('title1', $data);
        */
    }


    /**
     * @depends testCreatePresenter
     */
    public function _testRemovePresenter()
    {
        $publicModules = $this->moduleFacade->findUnSyncedPublicStaticPages();

        $testPresenter      = "Test";
        $publicModule       = array_keys($publicModules)[0];
        $presenterFullClass = ucfirst($publicModule) . "Module\\Presenters\\TestPresenter";

        $presenterClass = $this->pageFacade->presenterExists($publicModule, $testPresenter);
        $this->assertEquals($presenterFullClass, $presenterClass, "presenter neexistuje, nemáme co mazat");

        $this->pageFacade->removePresenter($publicModule, $testPresenter);
        $this->assertFalse($this->pageFacade->presenterExists($publicModule, $testPresenter), "presenter stále existuje");
    }


    public function testAddPage()
    {
        $publicModules = $this->moduleFacade->findUnSyncedPublicStaticPages();

        $testPresenter      = "Test";
        $testPage           = "default";
        $publicModule       = array_keys($publicModules)[0];
        $presenterFullClass = ucfirst($publicModule) . "Module\\Presenters\\TestPresenter";

        if (!$this->pageFacade->presenterExists($publicModule, $testPresenter)) {
            // vytvoříme presenter v prvním public modulu
            $this->assertNotEmpty($phpFile = $this->pageFacade->createPresenter($publicModule, $testPresenter), 'presenter se nepodařilo vytvořit');
        }

        $presenterClass = $this->pageFacade->presenterExists($publicModule, $testPresenter);
        $this->assertEquals($presenterFullClass, $presenterClass, "presenter neexistuje, nemáme co mazat");

        $this->pageFacade->addPage($publicModule, $testPresenter, $testPage);

    }


    /**
     * @group test
     */
    public function testCallDefaultPage()
    {
        $params = array(
            'action' => 'default',
        );

        $this->init('Front:Test');

        $request  = $this->getRequest($params);
        $response = $this->getResponse($request);

        $this->assertInstanceOf('Nette\Application\Responses\TextResponse', $response);

        $html = $this->createHtmlFromResponse($response, false);

        // je založena prázdná stránka ? šablona
        $this->assertSelectEquals('body', 'empty page', true, $html, "Není prázdná stránka");

        // je založena stránka v db?
        $this->assertNotEmpty($pageInDB = $this->pageFacade->getPageRepository()->findOneBy(['module' => 'front', 'presenter' => 'test', 'action' => 'default']), 'Stránka není v DB');




    }


    public function _testCallApplication()
    {
        $app = $this->getContainer()->getByType('Nette\Application\Application')->run();
        dump($app);
        die();
    }





    /**
     * @depends testAddPage
     */
    public function testRemovePage()
    {
        $publicModules = $this->moduleFacade->findUnSyncedPublicStaticPages();

        $testPresenter      = "Test";
        $testPage           = "default";
        $publicModule       = array_keys($publicModules)[0];
        $presenterFullClass = ucfirst($publicModule) . "Module\\Presenters\\TestPresenter";

        $presenterClass = $this->pageFacade->presenterExists($publicModule, $testPresenter);
        $this->assertEquals($presenterFullClass, $presenterClass, "presenter neexistuje, nemáme co mazat");

        // existuje naše stránka?
        $phpFile = $this->pageFacade->getPresenterPhpGenerator($publicModule, $testPresenter);

        dump(spl_object_hash($phpFile));

        $presenterClassType = $phpFile->getClassType($presenterClass);
        $this->assertArrayHasKey('renderDefault', $presenterClassType->getMethods(), 'metoda neexistuje, nemáme co mazat.');

        $this->pageFacade->removePage($publicModule, $testPresenter, $testPage);



//        $tempDir = $this->getContainer()->parameters['tempDir'];
//        \Tester\Helpers::purge($tempDir);

        // neexistuje naše stránka?
        $phpFile            = $this->pageFacade->getPresenterPhpGenerator($publicModule, $testPresenter);
        dump(spl_object_hash($phpFile));

        Debugger::$maxLength = 5000;
        dump((string) $phpFile);

        $presenterClassType = $phpFile->getClassType($presenterClass);
        $this->assertArrayNotHasKey('renderDefault', $presenterClassType->getMethods(), 'metoda stále existuje, nesmazala se');
    }



}