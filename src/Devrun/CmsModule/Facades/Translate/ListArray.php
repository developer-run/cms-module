<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ListArray.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\Translate;


class ListArray
{

    /** @var array */
    private $_translateList = [];

    /**
     * ListArray constructor.
     */
    public function __construct(array $data, $translateId = 'site', $separator = '.')
    {
        $this->createTranslateListFromArray($data, $translateId, $separator);
    }


    protected function createTranslateListFromArray(array $data, $translateId = 'site', $separator = '.')
    {
        $params = [
            'id'        => $translateId,
            'separator' => $separator,
        ];

        array_walk($data, [$this, "arrayWalk"], $params);
        return $this->_translateList;
    }



    public function arrayWalk($value, $key, $params)
    {
        $id        = $params['id'];
        $separator = $params['separator'];
        $params    = [
            'id'        => "{$id}{$separator}{$key}",
            'separator' => $separator,
        ];

        if (is_array($value)) {
            array_walk($value, [$this, "arrayWalk"], $params);

        } elseif (is_string($value)) {
            $this->_translateList[$id][$key] = $value;
        }
    }


    /**
     * @return array
     */
    public function getTranslateList()
    {
        return $this->_translateList;
    }


}