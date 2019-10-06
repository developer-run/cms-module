<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2018
 *
 * @file    UsersPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\Application\UI\Presenter\TImgStoragePipe;
use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Repositories\DomainRepository;
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\CmsModule\Repositories\PageRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\ContestModule\Repositories\PageCaptureRepository;
use Devrun\Doctrine\Entities\UserEntity;
use Devrun\Doctrine\Repositories\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Monolog\Logger;
use Nette\Forms\Container;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Random;
use Nette\Utils\Validators;
use Tracy\Debugger;

class UsersPresenter extends AdminPresenter
{

    use TImgStoragePipe;

    /** @var UserRepository @inject */
    public $userRepository;

    /** @var DomainRepository @inject */
    public $domainRepository;

    /** @var PackageRepository @inject */
    public $packageRepository;

    /** @var RouteRepository @inject */
    public $routeRepository;

    /** @var PageRepository @inject */
    public $pageRepository;


    /** @var PageCaptureRepository _@_inject */
    public $pageCaptureRepository;



    /** @var EntityManager @inject */
    public $em;

    /** @var IMailer @inject */
    public $mailer;

    /** @var Logger @inject */
    public $logger;


    /** @var bool sending email? (DI) */
    private $emailSending = true;

    /** @var string sending from email? (DI) */
    private $emailFrom;


    public function handleDelete($id)
    {
        /** @var UserEntity $entity */
        if (!$entity= $this->userRepository->find($id)) {
            $this->flashMessage('Záznam nenalezen', 'danger');
            $this->ajaxRedirect();
        }

        $this->em->remove($entity)->flush();
        $this->flashMessage("Záznam {$entity->getEmail()} smazán", 'success');

        if ($this->isAjax()) {
            $this->redrawControl('flash');
            $this['usersGridControl']->reload();
        }

        $this->ajaxRedirect();
    }


    public function resetPassword($ids)
    {
        /** @var UserEntity[] $users */
        if ($users = $this->userRepository->findBy(['id' => $ids])) {

            foreach ($users as $user) {
                $psw = Random::generate();
                $user->generateNewPassword($psw);
                $this->userRepository->getEntityManager()->persist($user);

                if ($this->emailSending) {
                    $this->sendMail($user);
                }
            }


            $this->userRepository->getEntityManager()->flush();
        }


        $this->ajaxRedirect('default', null, ['flash']);
    }

    /**
     * send email
     *
     * @param $mail
     */
    private function sendMail(UserEntity $userEntity)
    {
        /** @var AdminPresenter $presenter */
        $presenter  = $this->getPresenter();
//        $translator = $this->getTranslator();
//        $title      = $translator->translate('userPage.management');
//        $userEntity = $this->getExistUserEntity($mail);

        $latte  = new \Latte\Engine;
        $params = [
            'url'      => $presenter->link("//:Cms:Login:forgottenPassword"),
            'link' => $presenter->link("//:Cms:Login:changePassword", ['id' => $userEntity->getId(), 'code' => $userEntity->getNewPassword()]),
        ];

        $message = new Message();
        $message->setFrom($this->emailFrom)
            ->addTo($userEntity->getEmail())
            ->setHtmlBody($latte->renderToString(__DIR__ . "/templates/Users/mailSendReset.latte", $params));

        $this->mailer->send($message);
//        $this->logger->info("{$userEntity->getRole()} {$userEntity->getUsername()} sendMail", ['type' => LogEntity::ACTION_ACCOUNT, 'target' => $userEntity, 'action' => 'email send ok']);

        $message = "Uživateli `{$userEntity->getEmail()}` byl zaslán e-mail pro aktivaci nového přístupu";
        $presenter->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Zaslání přístupu", FlashMessageControl::TOAST_INFO);
    }



    public function renderDefault()
    {


    }



    protected function createComponentUsersGridControl($name)
    {

        $grid = new DataGrid();
        $grid->setRefreshUrl(false)->setRememberState(false);


        $model = $this->userRepository->createQueryBuilder('a')
            ->where('a.role != :role')->setParameter('role', UserEntity::ROLE_SUPERUSER);


        $grid->setDataSource($model);

        $grid->addColumnDateTime('inserted', 'Záznam vložen')
            ->setAlign('text-left')
            ->setSortable()
            ->setFitContent()
            ->setFilterDateRange();

        $grid->addColumnText('firstName', 'Jméno')
            ->setSortable()
            ->setEditableCallback(function ($id, $value) use ($grid) {
                if (Validators::is($value, $validate = 'string:3..32')) {
                    if ($entity= $this->userRepository->find($id)) {
                        $entity->firstName = $value;
                        $this->em->persist($entity)->flush();
                        $this->flashMessage("Uživatel {$entity->username} upraven", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }
                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }
            })
            ->setFilterText();


        $grid->addColumnText('lastName', 'Příjmení')
            ->setSortable()
            ->setEditableCallback(function ($id, $value) use ($grid) {
                if (Validators::is($value, $validate = 'string:3..32')) {
                    if ($entity= $this->userRepository->find($id)) {
                        $entity->lastName = $value;
                        $this->em->persist($entity)->flush();
                        $this->flashMessage("Uživatel {$entity->username} upraven", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }
                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }
            })
            ->setFilterText();

        $grid->addColumnText('email', 'E-mail')
            ->setSortable()
            ->setEditableCallback(function ($id, $value) use ($grid) {
                $validate = 'string:3..32';
                if (Validators::isEmail($value) && Validators::is($value, $validate)) {
                    if ($entity= $this->userRepository->find($id)) {
                        $entity->email = $value;
                        $this->em->persist($entity)->flush();
                        $this->flashMessage("Uživatel {$entity->email} upraven", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }
                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }

            })
            ->setFilterText();

        $grid->addColumnText('username', 'Username')
            ->setSortable()
            ->setEditableCallback(function ($id, $value) use ($grid) {
                $validate = 'string:3..32';
                if (Validators::isEmail($value) && Validators::is($value, $validate)) {
                    if ($entity= $this->userRepository->find($id)) {
                        $entity->username = $value;
                        $this->em->persist($entity)->flush();
                        $this->flashMessage("Uživatel {$entity->username} upraven", 'success');
//                        $this['usersGridControl']->redrawItem($id);
                        $this->ajaxRedirect('this', null, 'flash');
                    }
                    return $value;

                } else {
                    $message = "input not valid [$value != $validate]";
                    return $grid->invalidResponse($message);
                }

            })
            ->setFilterText();

        $grid->addColumnStatus('role', 'Role')
            ->setCaret(true)
            ->addOption('guest', 'host')
            ->setClass('btn-info btn-block')
            ->endOption()
            ->addOption('member', 'člen')
            ->setClass('btn-primary btn-block')
            ->endOption()
//            ->addOption('customer', 'správce')
//            ->setClass('btn-info btn-block')
//            ->endOption()
            ->addOption('admin', 'admin')
            ->setClass('btn-danger btn-block')
            ->endOption()
            ->setSortable()
            ->onChange[] = function ($id, $value) {
            if ($entity= $this->userRepository->find($id)) {
                $entity->role = $value;
                $this->em->persist($entity)->flush();
                $this->flashMessage("Uživatel {$entity->username} upraven", 'success');
                $this['usersGridControl']->redrawItem($id);
                $this->ajaxRedirect('this', null, 'flash');
            }

//                $this['mediaGridControl']->redrawItem($id);
//                $this['templatesGridControl']->reload();
        };

        $grid->getColumn('role')->setFilterSelect([
            '' => $this->translator->translate('admin.roles.all'),
            'guest' => $this->translator->translate('admin.roles.guest'),
            'member' => $this->translator->translate('admin.roles.member'),
            'admin' => $this->translator->translate('admin.roles.admin')
        ])->addAttribute('class', 'btn-block btn');



        $grid->addAction('delete', 'Smazat', 'delete!')
            ->setIcon('trash fa-2x')
            ->setClass('ajax btn btn-xs btn-danger')
            ->setConfirm(function ($item) {
                return "Opravdu chcete smazat záznam [id: {$item->id}; {$item->email}]?";
            });


        $grid->addInlineAdd()
            ->setPositionTop()
            ->onControlAdd[] = function (Container $container)  {
            $container->addText('id', '')->setAttribute('readonly');//->setValue($this->editTemplateId);
            $container->addText('firstName');
            $container->addText('lastName');
            $container->addText('username');
            $container->addText('email', 'E-mail');
//            $container->addSelect('role', '', ['guest' => 'host', 'member' => 'člen', 'admin' => 'správce']);
            $container->addSelect('role', '', ['guest' => 'admin.roles.guest', 'member' => 'admin.roles.member', 'admin' => 'admin.roles.admin']);
        };

        $p = $this;

        $grid->getInlineAdd()->onSubmit[] = function($values) use ($p) {

            /**
             * Save new values
             */

//            dump($values);
//            die();


            try {
                $id = $values->id;

                /** @var UserEntity $entity */
                if (!$entity = $this->userRepository->find($id)) {
                    $entity = new UserEntity();
                }

                foreach ($values as $key => $value) {
                    if (isset($entity->$key)) {
                        $entity->$key = $value;
                    }
                }

                if (!$entity->getId()) {
                    $entity->setPassword(123123);
                }

                $this->em->persist($entity)->flush();

            } catch (UniqueConstraintViolationException $e) {

                $message = "user `{$entity->getUsername()}` exist, [error code {$e->getErrorCode()}]";
                $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "User add error", FlashMessageControl::TOAST_DANGER);
                $this->ajaxRedirect('this', null, ['flash']);
                return;
            }

            $message = "Record with values [{$entity->getUsername()}] was added!";
            $p->flashMessage($message, FlashMessageControl::TOAST_TYPE, 'Správa uživatelů', FlashMessageControl::TOAST_SUCCESS);

            $this['usersGridControl']->reload();
            $this->ajaxRedirect('this', null, ['flash']);
        };



        $grid->addGroupAction('Reset password')->onSelect[] = [$this, 'resetPassword'];

        $detail = $grid->setItemsDetail(__DIR__ . '/templates/Users/#user_grid_detail.latte');
        $detail->setTemplateParameters([
            '_imgStorage' => $this->imgStorage
        ]);

        $grid->setTranslator($this->translator);

        return $grid;
    }


    public function getUserPackages(UserEntity $userEntity)
    {
        return $this->packageRepository->findBy(['user' => $userEntity], ['module' => 'ASC']);
    }

    /**
     * @todo musíme vyřešit jinak, nezávysle na modulu contest
     *
     * @param PackageEntity $package
     * @return \Devrun\PhantomModule\Entities\ImageEntity|null
     */
    public function getImage(PackageEntity $package)
    {
        $image = $this->pageCaptureRepository->findImageByRouteCriteria([
            'package.module' => $package->getModule(),
            'package.name'   => $package->getName()
        ]);

        return $image;
    }


    public function getEditLink(PackageEntity $package)
    {
        if ($pageEntity = $this->pageRepository->findOneBy(['module' => $package->getModule(), 'lvl' => 0])) {
            return $pageEntity->id;
        }

        return null;
    }


    /**
     * @param bool $emailSending
     *
     * @return $this
     */
    public function setEmailSending($emailSending)
    {
        $this->emailSending = (bool)$emailSending;
        return $this;
    }

    /**
     * @param string $emailFrom
     *
     * @return $this
     */
    public function setEmailFrom($emailFrom)
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }


}