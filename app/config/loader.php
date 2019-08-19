<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->commonDir,
        $config->application->libraryDir,
    ]
)->register();

/*
 * 注册命名空间
 */
$loader->registerNamespaces([
    'common' => $config->application->commonDir,
    'app\controllers\admin' => $config->application->adminDir,
])->register();

