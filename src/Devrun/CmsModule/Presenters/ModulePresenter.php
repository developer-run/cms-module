<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ModulePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Administration\ICmsMode;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\ImagesEntity;
use Devrun\CmsModule\Entities\ImagesTranslationEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Entities\RouteTranslationEntity;
use Devrun\CmsModule\Facades\Images\ImageStorageManager;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\CmsModule\Storage\ImageManageStorage;
use Devrun\Storage\ImageStorage;
use Devrun\Utils\Arrays;
use Devrun\Utils\FileTrait;
use Flame\Utils\Finder;
use JonnyW\PhantomJs\Client;
use JonnyW\PhantomJs\Http\CaptureRequest;
use Kdyby\Events\Event;
use Kdyby\Events\EventManager;
use Nette\Application\IPresenter;
use Nette\Application\PresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\Context;
use Nette\Utils\Strings;
use Tracy\Debugger;

class ModulePresenter extends AdminPresenter
{

    use FileTrait;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var ImageStorage @inject */
    public $imageStorage;








    public function handleUpdate($name)
    {
        $this->moduleFacade->onUpdate($this->moduleFacade, $name);

        $message = "Modul `$name` updatován";

        $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Module update", FlashMessageControl::TOAST_INFO);
        $this->ajaxRedirect('this', null, ['flash']);
    }


    public function handleDeleteUnknownImages()
    {
        $params = $this->context->getParameters();
        $publicDir = $params['publicDir'];
        $files = Finder::findFiles('*')->exclude(['*.fit.*', '*.fill.*', '*.shrink_only.*', '*.stretch.*', '*.exact.*', '*no_image.*'])->from($publicDir);
        $em = $this->routeRepository->getEntityManager();

        $all = count($files);
        $deleted = 0;
        $limit = 500;


        /** @var \SplFileInfo $file */
        foreach ($files as $key => $file) {

            if ($pathInDB = Strings::after($key, "web/www/")) {
                if (!$record = $em->getRepository(ImagesTranslationEntity::class)->findOneBy(['path' => $pathInDB])) {
                    if ($deleted++ > $limit) break;

                    $identifier = trim(Strings::after($key, $publicDir), '/');
                    $this->imageStorage->delete($identifier);

                    if (file_exists($key)) {
                        unlink($key);
                    }

                }
            }
        };

        $message = "smazáno $deleted z $all";

        if ($deleted == 0) {
            self::removeEmptySubFolders($publicDir);
        }

        $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Module update", FlashMessageControl::TOAST_INFO);
        $this->ajaxRedirect('this', null, ['flash']);
    }


    public function garbageRoutes($ids)
    {
        $deleted = 0;

        foreach ($ids as $id) {
            $deleted += $this->garbageRoute($id);
        }

        $message = "Routes `" . implode(",", $ids) . " vyčištěny ($deleted items)";

        $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Module update", FlashMessageControl::TOAST_INFO);
        $this->ajaxRedirect('this', null, ['flash']);
    }


    private function garbageRoute($id)
    {
        $deleted = 0;

        /** @var RouteEntity $route */
        if ($route = $this->routeRepository->findOneBy(['translations.id' => $id])) {
            $page = $route->getPage();

            $presenterName = ucfirst($page->getModule()) .":" . ucfirst($page->getPresenter());

            $presenterFactory = $this->context->getByType('Nette\Application\IPresenterFactory');

            /** @var IPresenter $presenter */
            $presenter = $presenterFactory->createPresenter($presenterName);
            $presenter->autoCanonicalize = FALSE;

            $method = "POST";
            $params = [
                'cmsMode' => ICmsMode::GARBAGE_MODE,
                'package' => $route->getPackage()->getId(),
                'routeId' => $route->getId(),
                'action'  => $page->getAction(),
            ];
            $post   = [
                'cmsMode'  => 'imgGarbage',
            ];
            $files  = [];

            $request  = new Request($presenterName, $method, $params, $post, $files);
            $response = $presenter->run($request);

            if ($response instanceof TextResponse) {

                $html = (string)$response->getSource();

                /** @var Template $template */
                $template = $response->getSource();

                /** @var ImageManageStorage $photo */
                $photo = $template->getParameters()['_imageStorage'];

                $savedImages = $photo->getImageEntities();
                $useImages = $photo->getUseInPageImages();

                if ($savedImages && $useImages) {

                    /*
                     * [ [namespace[systemName]], [namespace[systemName]] ... ]
                     */
                    /** @var ImagesEntity[] $diffs */
                    $diffs = Arrays::arrayRecursiveDiff($savedImages, $useImages);

                    /** @var ImagesEntity[] $diff */
                    foreach ($diffs as $diff) {
                        $this->routeRepository->getEntityManager()->remove($diff->getIdentify())->remove($diff);
                        $deleted++;
                    }

                    if ($diffs) {
                        $this->routeRepository->getEntityManager()->flush();
                    }
                }
            }
        }

        return $deleted;
    }


    public function handleGarbageRoute($id)
    {
        $deleted = $this->garbageRoute($id);

        $message = "Route `$id` vyčištěna ($deleted items)";

        $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Module update", FlashMessageControl::TOAST_INFO);
        $this->ajaxRedirect('this', null, ['flash']);
    }




    protected function createComponentModulesGridControl($name)
    {
//        $this->testGetPage();


        $grid = $this->createGrid($name);
        $grid->setTranslator($this->translator);
        $grid->setItemsPerPageList([15, 30]);

        $modules = $this->moduleFacade->findModules();

        $grid->setPrimaryKey('name');
        $grid->setDataSource($modules);

        $grid->addColumnText('name', 'name');
        $grid->addColumnText('version', 'Version');
        $grid->addColumnText('defaultPageName', 'DefaultPageName');


        $grid->addAction('update', 'Update', 'update!')
            ->setIcon('eye fa-2x')
            ->setClass('_ajax btn btn-xs btn-info')
            ->setConfirm(function ($item) {
                return "Opravdu chcete updatovat modul [id: {$item->name}]?";
            });



        return $grid;
    }


    protected function createComponentRoutesGridControl($name)
    {
//        $this->testGetPage();
//        $this->testFindUnknownImages();

        $grid = $this->createGrid($name);
        $grid->setRefreshUrl(false);
        $grid->setTranslator($this->translator);
        $grid->setItemsPerPageList([15, 30]);

        $query = $this->routeRepository->createQueryBuilder()
            ->select('rt')
            ->from(RouteTranslationEntity::class, 'rt')
            ->leftJoin('rt.translatable', 't');


        $grid->setDataSource($query);

        $grid->addColumnText('url', 'Url')
            ->setSortable()
            ->setFilterText();


        $grid->addColumnText('uri', 'Uri', 'translatable.uri')
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.uri LIKE :uri')->setParameter('uri', "%$value%");
            });


        //        $grid->addColumnText('defaultPageName', 'DefaultPageName');


        $grid->addAction('garbage', 'Garbage', 'garbageRoute!')
            ->setIcon('eye fa-2x')
            ->setClass('_ajax btn btn-xs btn-warning')
            ->setConfirm(function ($item) {
                return "Opravdu chcete vyčistit routu [id: {$item->url}]?";
            });

        $grid->addGroupAction('Delete routes images')->onSelect[] = [$this, 'garbageRoutes'];

        return $grid;
    }









    /**
     * principt získání stránky, lze dohledat img ze služby
     */
    private function testGetPage()
    {
        $presenterName = "Pexeso:Homepage";


        /** @var PresenterFactory $presenterFactory */
        $presenterFactory                  = $this->context->getByType('Nette\Application\IPresenterFactory');
        $presenter                   = $presenterFactory->createPresenter($presenterName);

        $presenter->autoCanonicalize = FALSE;


//        dump($presenter);

        $method = "POST";
        $params = [
            'cmsMode' => ICmsMode::GARBAGE_MODE,
            'package' => 3,
            '_route' => 60,
            'action'  => 'default',
        ];
        $post = [
            'cmsMode'  => 'imgGarbage',
        ];
        $files = [];



        $request = new Request($presenterName, $method, $params, $post, $files);


//        dump($request);
//        die();


        $response = $presenter->run($request);

        if ($response instanceof TextResponse) {
            $html = (string)$response->getSource();
//            echo $html;


            /** @var Template $template */
            $template = $response->getSource();

            /** @var ImageStorageManager $photo */
            $photo = $template->getParameters()['photo'];

            $savedImages = $photo->getImagesEntity();
            $useImages = $photo->getUseInPageImages();

            if ($savedImages && $useImages) {
                /*
                 * [ [namespace[systemName]], [namespace[systemName]] ... ]
                 */
                $diffs = Arrays::arrayRecursiveDiff($savedImages, $useImages);

                foreach ($diffs as $diff) {
                    /** @var ImagesEntity[] $diff */
                    foreach ($diff as $item) {
                        $this->routeRepository->getEntityManager()->remove($item)->flush();


                        dump("delete");
                        die();

                    }
                }
            }





//            $q = array_intersect_key($useImages, $savedImages);


            dump($q);

//            dump($html);
        }


        dump($request);
        dump($response);


        return;

        $https_user = "admin";
        $https_password = 123123;

//        $url = "http://localhost/nivea/devrun-advent_calendar/form/day/1";
//        $url = "http://localhost/nivea/devrun-advent_calendar/form/day/2";
//        $url = "http://localhost/nivea/devrun-advent_calendar/thank-you";
        $url = "http://localhost/pixman/souteze.pixman.cz/web/www/pexeso";

        $post = [
            'layout' => false,
//            'cmsMode' => true,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_USERAGENT, 'admin');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_USERPWD, "$https_user:$https_password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $pageHtml = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);



        dump($info);
        dump($pageHtml);
        die();



    }


    public function renderPhantom()
    {

        $client = Client::getInstance();

        $client->getEngine()->setPath('/var/www/html/pixman/souteze.pixman.cz/web/bin/phantomjs');

        $width  = 800;
        $height = 600;
        $top    = 0;
        $left   = 0;


        /** @var CaptureRequest $request */
//        $request = $client->getMessageFactory()->createCaptureRequest('http://localhost/pixman/souteze.pixman.cz/web/www/pexeso?foto=1');
//        $request = $client->getMessageFactory()->createCaptureRequest('https://souteze.pixman.cz/pexeso');
//        $request = $client->getMessageFactory()->createCaptureRequest('http://souteze.pixman.local/pexeso');
//        $request = $client->getMessageFactory()->createCaptureRequest('http://souteze.pixman.local/pexeso/marama');
//        $request = $client->getMessageFactory()->createCaptureRequest('http://souteze.pixman.local/pexeso/marama/form');
        $request = $client->getMessageFactory()->createCaptureRequest('http://souteze.pixman.local/pexeso/dekujeme');

        $request
            ->setOutputFile(('sample.jpg'))
//            ->setViewportSize(3000, 2625)
            ->setViewportSize(1220, 880);



//        $request->setViewportSize($width, $height);
//        $request->setCaptureDimensions($width, $height, $top, $left);

//        $request->setOutputFile('/var/www/html/pixman/souteze.pixman.cz/web/www/file.jpg');


        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);

        dump($response);
        die();


        if($response->getStatus() === 200) {

            // Dump the requested page content
            echo $response->getContent();
        }

        $q = $request->getOutputFile();

        dump($q);

        dump($client);
        die();




        die("ASd");


    }



}