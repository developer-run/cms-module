<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    ProfilePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Forms\IProfileFormFactory;
use Devrun\CmsModule\Forms\ProfileForm;
use Devrun\CmsModule\Repositories\PackageRepository;
use Devrun\CmsModule\Entities\UserEntity;

class ProfilePresenter extends AdminPresenter
{

    /** @var IProfileFormFactory @inject */
    public $profileFormFactory;

    /** @var PackageRepository @inject */
    public $packageRepository;



    public function renderDefault()
    {
        $this->template->userEntity = $userEntity = $this->getUserEntity();
        $this->template->packages   = $this->packageRepository->countBy(['user' => $userEntity]);
    }



    protected function createComponentProfileForm($name)
    {
        $form = $this->profileFormFactory->create();
        $form->setTranslator($this->translator->domain("admin.forms.$name"));

        if (!$userEntity = $this->getUserEntity()) {
            $userEntity = new UserEntity();
        }

        $form->create()
            ->bootstrap4Render()
            ->bindEntity($userEntity)
            ->onSuccess[] = function (ProfileForm $form, $values) use ($name) {

            /** @var UserEntity $entity */
            $entity = $form->getEntity();



            $em = $form->getEntityMapper()->getEntityManager();
            $em->persist($entity)->flush();

            $message = $this->translator->translate("admin.profilePage.userUpdated", null, ['user' => $entity->getUsername()]);
            $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, $this->translator->translate("admin.profilePage.title"), FlashMessageControl::TOAST_SUCCESS);
            $this->ajaxRedirect(':Cms:Default:');


        };

        return $form;
    }



}