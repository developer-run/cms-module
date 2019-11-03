<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    TImageStoragePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;


use Devrun\CmsModule\Storage\ImageManageStorage;

trait TImageStoragePipe
{

    /** @var ImageManageStorage */
    public $imageStorage;


    public function injectImageStorage(ImageManageStorage $imageStorage) {
        $this->imageStorage = $imageStorage;
        $this->template->_imageStorage = $imageStorage;
        $this->template->proxyUrl = '';
    }




}