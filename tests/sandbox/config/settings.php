<?php

return array(
	"modules" => array(
        'cms' => [
            'status' => 'uninstalled',
            'action' => '',
            'class' => 'Devrun\CmsModule\Module',
            'version' => '0.8.0',
            'path' => '%baseDir%',
            'autoload' => ['classmap' => ['src/']],
            'require' => [],
        ],
	),
);
