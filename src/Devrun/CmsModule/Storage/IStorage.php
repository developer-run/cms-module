<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    IStorage.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Storage;


interface IStorage
{

    /**
     * Read from cache.
     *
     * @param  string $key
     *
     * @return mixed|NULL
     */
    function load($key);

    /**
     * Writes item into the cache.
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  array  $dependencies
     *
     * @return void
     */
    function save($key, $data, array $dependencies = []);

    /**
     * Removes items from the cache by conditions.
     *
     * @param  array $conditions
     *
     * @return void
     */
    function clean(array $conditions);

    /**
     * Removes item from the cache.
     *
     * @return void
     */
    function clear($key);


}