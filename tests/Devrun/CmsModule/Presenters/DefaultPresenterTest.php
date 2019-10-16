<?php

namespace Devrun\CmsModule\Presenters;

use Devrun\Tests\Presenter;
use Tracy\Debugger;

/**
 * Class DefaultPresenterTest
 *
 * @internal DefaultPresenter::class
 * @package Devrun\CmsModule\Presenters
 */
class DefaultPresenterTest extends Presenter
{

    private $tested = DefaultPresenter::class;


    public function testNoLoggedIn()
    {
        $this->init('Cms:Default');

        $request   = $this->getRequest(['action' => 'default']);
        $response  = $this->getResponse($request);
        $presenter = $this->presenter;

        $this->assertInstanceOf('Nette\Application\Responses\RedirectResponse', $response);
        $this->assertEquals($this->uriCheck($presenter->link('//Login:')), $this->uriCheck($response->getUrl()));
    }


    /**
     * @throws \Nette\Utils\AssertionException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testRenderDefault()
    {
        $this->sendLoginForm();

        $this->init('Cms:Default');

        $params = array(
            'action' => 'default',
        );

        $request   = $this->getRequest($params);
        $response  = $this->getResponse($request);
        $presenter = $this->presenter;


        $html = $this->createHtmlFromResponse($response);

        $this->assertInstanceOf('Nette\Application\Responses\TextResponse', $response);
        $this->assertSelectCount('#defaultContentInfo  [class*="col-"]', 3, $html, "Počet komponent nesouhlasí");
    }


}
