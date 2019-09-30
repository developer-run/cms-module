<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    Common.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Utils;


class Common
{

    /**
     * @return bool is request from admin page
     */
    public static function isAdminRequest()
    {
        return ($agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'admin') == 'admin';
    }

    /**
     * @return bool is request from phantom
     */
    public static function isPhantomRequest()
    {
        return ($agent = isset($_SERVER['HTTP_USER_AGENT']) ? strpos($_SERVER['HTTP_USER_AGENT'], 'PhantomJS') : 0) > 0;
    }


}