<?php
/**
 * This file is part of Developer Run <Devrun>.
 * Copyright (c) 2019
 *
 * @file    PackageListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Listeners;


use Devrun\CmsModule\Entities\DomainEntity;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Facades\PackageFacade;
use Devrun\CmsModule\Facades\ThemeFacade;
use Devrun\CmsModule\Repositories\PackageRepository;
use Kdyby\Events\Subscriber;

class PackageListener implements Subscriber
{

    /** @var ThemeFacade */
    private $themeFacade;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var PackageFacade */
    private $packageFacade;


    /**
     * PackageListener constructor.
     *
     * @param ThemeFacade       $themeFacade
     * @param PackageRepository $packageRepository
     */
    public function __construct(ThemeFacade $themeFacade, PackageFacade $packageFacade)
    {
        $this->themeFacade       = $themeFacade;
        $this->packageFacade     = $packageFacade;
        $this->packageRepository = $packageFacade->getPackageRepository();
    }


    /**
     * @param PackageEntity      $newPackage
     * @param PackageEntity|null $oldPackage
     */
    public function onCopyPackage(PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        if ($oldPackage) {
            $newPackage->setModule($module = $oldPackage->getModule());
        }

        $this->themeFacade->settingsFromPackage($newPackage)->generateThemeCss()->save();

//        dump($oldPackage);
//        dump($newPackage);

        $this->themeFacade->copyPackageSourcesToDestinationSources($oldPackage, $newPackage);


//        die(__METHOD__);
    }



    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            PackageFacade::EVENT_COPY_PACKAGE => [array('onCopyPackage', 100)],
        ];
    }
}