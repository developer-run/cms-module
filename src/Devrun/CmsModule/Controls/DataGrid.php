<?php
/**
 * This file is part of devrun-souteze.
 * Copyright (c) 2018
 *
 * @file    DataGrid.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Controls;

use Ublaboo\DataGrid\DataModel;
use Ublaboo\DataGrid\Exception\DataGridColumnNotFoundException;
use Ublaboo\DataGrid\Utils\Sorting;

class DataGrid extends \Ublaboo\DataGrid\DataGrid
{

    /**
     * @return array
     */
    public function getFilterData()
    {
        $dataSource = $this->getDataSource();
        $pagination = $this->getPaginator();

        $sort = $this->sort;

        foreach ($sort as $key => $order) {
            unset($sort[$key]);

            try {
                $column = $this->getColumn($key);

            } catch (DataGridColumnNotFoundException $e) {
                continue;
            }

            $sort[$column->getSortingColumn()] = $order;
        }

        $sorting = new Sorting($sort);
        $model   = new DataModel($dataSource, $this->getPrimaryKey());

        return $model->filterData($pagination, $sorting, $this->assembleFilters());
    }


    public function invalidResponse($message = null)
    {
        header('HTTP/1.1 406 Not Acceptable');
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode(array('message' => 'ERROR ' . $message, 'code' => 1337)));
    }

}