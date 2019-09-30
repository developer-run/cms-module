<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2017
 *
 * @file    PageRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Repositories;

use Devrun\CmsModule\Entities\PageEntity;
use Gedmo\Tree\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Tracy\Debugger;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class PageRepository extends EntityRepository
{
    use NestedTreeRepositoryTrait;

    /** @var array */
    private $pagesHtml = [];


    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->initializeTreeRepository($em, $class);
    }


    /**
     * @param $url
     *
     * @return string
     */
    public function getPageHtmlFromUrl($url)
    {
        if (!isset($this->pagesHtml[$url])) {
            $https_user = "admin";
            $https_password = 'jihg11';

//        $url = "http://localhost/nivea/devrun-advent_calendar/form/day/1";
//        $url = "http://localhost/nivea/devrun-advent_calendar/form/day/2";
//        $url = "http://localhost/nivea/devrun-advent_calendar/thank-you";

            $post = [
                'layout' => 'cmsLayout',
            ];

//            $encodedAuth = base64_encode($https_user.":".$https_password);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_USERAGENT, 'admin');
//            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$encodedAuth));
//            curl_setopt($ch, CURLOPT_USERPWD, "$https_user:$https_password");
//            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $pageHtml = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $this->pagesHtml[$url] = $pageHtml;
        }

        return $this->pagesHtml[$url];
    }


    /**
     * @param $url
     *
     * @return array of page css
     */
    public function getPageStyles($url)
    {
        return $this->isPageMask($url, $mask = 'data-pf-placeholder="head"')
            ? $this->getPageContentFromFilter($url, "[$mask]")
            : [];
    }


    /**
     * @param $url
     *
     * @return array of page scripts
     */
    public function getPageJavaScripts($url)
    {
        return $this->isPageMask($url, $mask = 'data-pf-placeholder="scripts"')
            ? $this->getPageContentFromFilter($url, "[$mask]")
            : [];
    }

    private function isPageMask($url, $mask)
    {
        if ($pageHtml = $this->getPageHtmlFromUrl($url)) {
            return preg_match("%$mask%", $pageHtml);
        }

        return false;
    }


    /**
     * @param $url
     *
     * @return string
     */
    public function getPageContentFromUrl($url)
    {
        if ($pageHtml = $this->getPageHtmlFromUrl($url)) {

//            Debugger::$maxLength = 5000;
//            dump($pageHtml);

            if (!$this->isPageMask($url, $mask = 'data-pf-placeholder="content"')) {
                return $pageHtml;
            }

//            echo $pageHtml;
//            die();


            /*
             * replace submit button -> span
             */
            $crawler = HtmlPageCrawler::create($pageHtml);

//            dump($crawler);
//            die("Asd");


            $pageContent = $crawler->filter("[$mask]");
            if ($pageContent->count() > 0) {
                $pageContentHtml = trim($pageContent->getInnerHtml());
                $crawler = HtmlPageCrawler::create($pageContentHtml);
            }

            /*
             * replace submit -> button
             */
            $button = $crawler->filter('button[type="submit"]');
            if ($button->count() > 0) {
                $content = $button->getInnerHtml();
                $class = $button->getAttribute('class');

                $span = HtmlPageCrawler::create("<span>" . $content . "</span>");
                $span->addClass($class);
                $span->setAttribute('title', 'send is disabled in administration mode');

                $button->replaceWith($span);
            }


            /*
             * replace <a></a> -> <span></span>
             */
            foreach ($links = $crawler->filter('a') as $link) {
                $_link = HtmlPageCrawler::create($link);
                $content = $_link->getInnerHtml();
                $class= $_link->getAttribute('class');
                $dataDomain= $_link->getAttribute('data-domain');
                $dataTranslate= $_link->getAttribute('data-translate');
                $editable= $_link->getAttribute('contenteditable');

                $span = HtmlPageCrawler::create("<span>" . $content . "</span>");
                $span->setAttribute('title', 'link is disabled in administration mode');
                if ($class) $span->setAttribute('class', $class);
//                if ($dataDomain) $span->setAttribute('data-domain', $dataDomain);
//                if ($dataTranslate) $span->setAttribute('data-translate', $dataTranslate);
//                if ($editable) $span->setAttribute('contenteditable', $editable);

                $_link->replaceWith($span);
            }


            /*
             * remove validation rules from inputs
             */
            foreach ($inputs = $crawler->filter('input') as $input) {
                $_input = HtmlPageCrawler::create($input);

                $_input->removeAttr('required');
                $_input->removeAttr('data-nette-rules');
            }


            /*
             * replace <form/> -> <span></span>
             */
            $forms = $crawler->filter('form');

            foreach ($forms as $form) {
                $_form = HtmlPageCrawler::create($form);
                $content = $_form->getInnerHtml();
                $class= $_form->getAttribute('class');
                $id= $_form->getAttribute('id');

                $div = HtmlPageCrawler::create("<div>" . $content . "</div>");
                $div->addClass($class);
                $div->setAttribute('id', $id);
                $div->setAttribute('title', 'link is disabled in administration mode');

                $_form->replaceWith($div);
            }

            //        Debugger::$maxLength = 200000;

            //        dump($links->saveHTML());
            //        dump($crawler->saveHTML());
            //        die();

            $html = $crawler->saveHTML();

        } else {
            $html = "<h2 class='text-danger text-center'>Stránku se nepodařilo načíst!</h2>";
        }

//        dump($html);
//        die();

        return $html;
    }



    /**
     * @param $filter
     *
     * @return array
     */
    private function getPageContentFromFilter($url, $filter)
    {
        $result = [];
        if ($pageHtml = $this->getPageHtmlFromUrl($url)) {
            $crawler = HtmlPageCrawler::create($pageHtml);
            $pageLinks = $crawler->filter($filter);

            foreach ($pageLinks as $pageLink) {
                $result[] = HtmlPageCrawler::create($pageLink)->saveHTML();
            }
        }

        return $result;
    }





}