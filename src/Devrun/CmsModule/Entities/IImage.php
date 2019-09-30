<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    IImages.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Entities;


interface IImage
{

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name);

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setIdentifier(string $name);

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setReferenceIdentifier(string $name);

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setAlt(string $name);

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path);

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function setNamespace(string $namespace);

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setSha(string $path);

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth(int $width);

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight(int $height);

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type);


    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getReferenceIdentifier();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getNamespace();

    /**
     * @return int
     */
    public function getWidth();

    /**
     * @return int
     */
    public function getHeight();


}