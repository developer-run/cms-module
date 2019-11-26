<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    LogPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Presenters;

use Nette\Application\Responses\FileResponse;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Html;

class LogPresenter extends AdminPresenter
{

    private $logs = [];


    public function actionDefault()
    {
        $logDir      = $this->context->getParameters()['logDir'];
        $wwwCacheDir = $this->context->getParameters()['wwwCacheDir'];

        $queryRepoLogs = [];
        $inc           = 1;
        foreach (Finder::findFiles('*.html')->in($logDir) as $key => $item) {
            FileSystem::copy($key, $targetFile = $wwwCacheDir . "/" . basename($key));

            $filePath            = $this->requestScript->getUrl()->basePath . "webTemp/" . basename($key);
            $queryRepoLogs[$inc] = ['id'           => $inc,
                                    'time'         => DateTime::from(filemtime($key)),
                                    'filename'     => basename($key),
                                    'originalPath' => $key,
                                    'filePath'     => $filePath];
            $inc++;
        }

        $this->logs = $queryRepoLogs;
    }



    public function handleDownload($id)
    {
        $log = $this->logs[$id];

        $logDir = $this->context->getParameters()['logDir'];
        $filename = $logDir . "/" . $log["filename"];

        if (file_exists($filename)) {
            $this->sendResponse(new FileResponse($filename, $filename));
        }
    }


    public function handleDelete($id)
    {
        $log = $this->logs[$id];

        $logDir = $this->context->getParameters()['logDir'];
        $filename = $logDir . "/" . $log["filename"];

        if (file_exists($filename)) {
            unlink($filename);
        }

        $this['grid']->reload();
    }

    public function deleteLogs($ids)
    {
        $logs = $this->logs;
        $logDir = $this->context->getParameters()['logDir'];

        foreach ($ids as $id) {
            $log = $logs[$id];
            $filename = $logDir . "/" . $log["filename"];

            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        $this['grid']->reload();
    }


    protected function createComponentGrid($name)
    {
        $grid = $this->createGrid($name);
        $grid->setDataSource($this->logs);

        $grid->addColumnText('id', 'Klíč')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnDateTime('time', 'Čas')
            ->setRenderer(function ($row) {
                return $row['time'];
            })
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('file', 'Log')
            ->setSortable()
            ->setRenderer(function ($log) {
                $result = Html::el('a')->addText($log['filename'])
                    ->href($log['filePath']);
                return $result;
            })
            ->setFilterText();

        $grid->addAction('download', '', 'download!')
            ->setIcon('download')
            ->setClass('btn btn-xs btn-default _ajax');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setClass('btn btn-xs btn-danger ajax');

        $grid->addGroupAction('Delete')->onSelect[] = [$this, 'deleteLogs'];

        return $grid;
    }


}