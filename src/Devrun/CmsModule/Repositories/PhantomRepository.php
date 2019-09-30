<?php
/**
 * This file is part of devrun
 * Copyright (c) 2019
 *
 * @file    PhantomRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use JonnyW\PhantomJs\Client;
use JonnyW\PhantomJs\Http\RequestInterface;
use JonnyW\PhantomJs\Message\Response;
use Nette\FileNotFoundException;

class PhantomRepository
{

    const TIMEOUT = 15000;

    /** @var Client */
    private $instance;

    /**
     * PhantomRepository constructor.
     *
     * @param $phantomBin string path to phantomjs
     */
    public function __construct($phantomBin)
    {
        $this->instance = Client::getInstance();
        if (!file_exists($phantomBin)) {
            throw new FileNotFoundException($phantomBin);
        }

        $this->instance->setPhantomJs($phantomBin);

//        $this->instance->getEngine()->setPath($phantomBin);
    }

    /**
     * @return Client
     */
    public function getInstance()
    {
        return $this->instance;
    }


    /**
     * @param $url string
     *
     * @return Response
     */
    public function getRawContent($url)
    {


        $request  = $this->getInstance()->getMessageFactory()->createRequest('GET', $url);
        $response = $this->getInstance()->getMessageFactory()->createResponse();


        // Send the request
        $this->getInstance()->send($request, $response);
//        $this->getInstance()->send($request, $response, 'capture.png');


        dump($response->getStatus());
        die();


        return $response;
    }


}