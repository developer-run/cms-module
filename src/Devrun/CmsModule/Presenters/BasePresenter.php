<?php


namespace Devrun\CmsModule\Presenters;

use Devrun\CmsModule\Utils\Common;

class BasePresenter extends \Devrun\Application\UI\Presenter\BasePresenter
{

    protected function beforeRender()
    {
        parent::beforeRender();

        $cmsClass = Common::isAdminRequest() ? ' in' : null;
        $this->template->pageClass = trim("main-wrapper ajax-fade {$this->template->pageClass}{$cmsClass}");
    }



}