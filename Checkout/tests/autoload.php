<?php

$modulePath = realpath(dirname(__FILE__) . '/../../../');
ini_set(
    'include_path',
    ini_get('include_path') . PATH_SEPARATOR
    . $modulePath
);
spl_autoload_register(function ($className) {
    $filePath = strtr(
        ltrim($className, '\\'),
        array(
            '\\' => '/',
            '_' => '/'
        )
    );

    @include $filePath . '.php';
});
