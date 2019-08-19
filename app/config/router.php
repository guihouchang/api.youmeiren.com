<?php

$router = $di->getRouter();

// Define your routes here
$modules = [
    "admin" => 'admin',
];

foreach ($modules as $key => $name)
{
    $router->add('/' . $key . '/:controller/:action/:params', [
        'namespace' => "app\\controllers" . ($name ? "\\$name" : ""),
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ]);
}


$router->handle();
