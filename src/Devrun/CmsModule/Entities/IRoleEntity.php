<?php
/**
 * This file is part of karl.pixman.cz.
 * Copyright (c) 2018
 *
 * @file    IRoleEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace CmsModule\Entities;


interface IRoleEntity
{

    const GUEST = 'guest';
    const MEMBER = 'member';
    const ADMIN = 'admin';
    const CUSTOMER = 'admin';
    const SUPERVISOR = 'supervisor';


}