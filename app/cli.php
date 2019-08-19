<?php
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VENDOR_PATH', BASE_PATH . '/vendor');

// 使用CLI工厂默认服务容器
$di = new CliDI();

/**
 * 注册自动加载器并告诉它注册任务目录
 */
$loader = new Loader();
$loader->registerDirs(
    [
        __DIR__ . '/tasks',
        __DIR__ . '/models',
        __DIR__ . '/common',
    ]
)->register();

/*
 * 注册命名空间
 */
$loader->registerNamespaces([
    'common' => __DIR__ . '/common',
])->register();

// 创建控制台应用程序
$console = new ConsoleApp();

$console->setDI($di);

$di->setShared("console", $console);

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $connection = new $class($params);

    return $connection;
});

/**
 * 集成redis服务
 */
$di->setShared('redis', function() {
    $config = $this->getConfig();
    $redis = new Redis();
    $redis->connect($config->redis->host, $config->redis->port);

    return $redis;
});

/**
 * Include Composer
 */
include VENDOR_PATH . '/autoload.php';

/**
 * 处理控制台参数
 */
$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {
    // 处理传入的参数
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    // Phalcon在这里做了相关的事情
    // ..
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}