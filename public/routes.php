<?php

// define routes

$routes = array(
    array(
        "pattern" => "features",
        "controller" => "home",
        "action" => "features"
    ),
    array(
        "pattern" => "home",
        "controller" => "home",
        "action" => "index"
    ),
    array(
        "pattern" => "index",
        "controller" => "home",
        "action" => "index"
    ),
    array(
        "pattern" => "genres",
        "controller" => "home",
        "action" => "genres"
    ),
    array(
        "pattern" => "events",
        "controller" => "home",
        "action" => "events"
    ),
    array(
        "pattern" => "listen",
        "controller" => "home",
        "action" => "listen"
    ),
    array(
        "pattern" => "video",
        "controller" => "home",
        "action" => "video"
    ),
    array(
        "pattern" => "video/:id",
        "controller" => "home",
        "action" => "videoDetail"
    )
);

// add defined routes
foreach ($routes as $route) {
    $router->addRoute(new Framework\Router\Route\Simple($route));
}

// unset globals
unset($routes);
