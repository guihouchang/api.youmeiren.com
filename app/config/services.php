<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Cache\Backend\Redis as PhalconRedis;
use Phalcon\Db\Profiler as ProfilerDb;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlPdo;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Events\Event;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'compiledPath' => $config->application->cacheDir,
                'compiledSeparator' => '_'
            ]);

            return $volt;
        },
        '.phtml' => PhpEngine::class

    ]);

    return $view;
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
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    return new Flash([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionAdapter();
    $session->start();

    return $session;
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

$di->set('modelsCache', function () {
    $config = $this->getConfig();
    $frontCache = new \Phalcon\Cache\Frontend\Data();
    $cache = new PhalconRedis($frontCache, [
        'host' => $config->redis->host,
        'port' => $config->redis->port,
    ]);

    return $cache;
});

$di->set(
    'profiler',
    function () {
        return new ProfilerDb();
    },
    true
);

$di->set(
    'db',
    function () use ($di) {
        $config = $this->getConfig();

        $eventsManager = new EventsManager();

        // Get a shared instance of the DbProfiler
        $profiler = $di->getProfiler();

        // Listen all the database events
        $eventsManager->attach(
            'db',
            function ($event, $connection) use ($profiler) {
                if ($event->getType() === 'beforeQuery') {
                    $profiler->startProfile(
                        $connection->getSQLStatement()
                    );
                }

                if ($event->getType() === 'afterQuery') {
                    $profiler->stopProfile();
                }
            }
        );

        $connection = new MysqlPdo(
            [
                'host'     =>  $config->database->host,
                'username' =>  $config->database->username,
                'password' =>  $config->database->password,
                'dbname'   =>  $config->database->dbname,
            ]
        );

        // Assign the eventsManager to the db adapter instance
        $connection->setEventsManager($eventsManager);

        return $connection;
    }
);


$di->set(
    'dispatcher',
    function () {
        // Create an event manager
        $eventsManager = new EventsManager();

        // Attach a listener for type 'dispatch'
        $eventsManager->attach(
            'dispatch',
            function (Event $event, $dispatcher) {
                // ...
            }
        );

        $dispatcher = new MvcDispatcher();

        // Bind the eventsManager to the view component
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    },
    true
);

$di->setShared(
    'transactions',
    function () {
        return new TransactionManager();
    }
);
