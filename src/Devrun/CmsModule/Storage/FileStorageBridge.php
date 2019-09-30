<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    FileStorageBridge.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Storage;

use Nette;

class FileStorageBridge implements IStorage
{

    /** @var \Nette\Caching\Storages\FileStorage */
    private $fileStorage;


    function __construct($path)
    {

        if (!is_dir($path)) {
            umask(0000);
            @mkdir($path, 0777, true);
        }

        $fileStorage = new Nette\Caching\Storages\FileStorage($path, new Nette\Caching\Storages\SQLiteJournal($path));
        $this->fileStorage = new Nette\Caching\Cache($fileStorage);
    }


    /**
     * Read from cache.
     *
     * @param  string $key
     *
     * @return mixed|NULL
     */
    function load($key)
    {
        return $this->fileStorage->load($key);
    }

    /**
     * Writes item into the cache.
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  array  $dependencies
     *
     * @return void
     */
    function save($key, $data, array $dependencies = array())
    {
        $this->fileStorage->save($key, $data, $dependencies);
    }

    /**
     * Removes items from the cache by conditions.
     *
     * @param  array $conditions
     *
     * @return void
     */
    function clean(array $conditions)
    {
        $this->fileStorage->clean($conditions);
    }

    /**
     * Removes item from the cache.
     *
     * @return void
     */
    function clear($key)
    {
        $this->fileStorage->remove($key);
    }
}