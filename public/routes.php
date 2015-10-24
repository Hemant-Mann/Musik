<?php

// define routes

$routes = array(
    // Main Navigation routes
    array(
        "pattern" => "home",
        "controller" => "home",
        "action" => "index"
    ),
    array(
        "pattern" => "index/:page",
        "controller" => "home",
        "action" => "index"
    ),
    array(
        "pattern" => "index",
        "controller" => "home",
        "action" => "index"
    ),
    array(
        "pattern" => "genres/:name/:page",
        "controller" => "home",
        "action" => "genres"
    ),
    array(
        "pattern" => "genres/:name",
        "controller" => "home",
        "action" => "genres"
    ),
    array(
        "pattern" => "genres",
        "controller" => "home",
        "action" => "genres"
    ),
    array(
        "pattern" => "videos",
        "controller" => "home",
        "action" => "videos"
    ),

    // user routes
    array(
        "pattern" => "login",
        "controller" => "users",
        "action" => "login"
    ),
    array(
        "pattern" => "signup",
        "controller" => "users",
        "action" => "signup"
    ),
    array(
        "pattern" => "profile",
        "controller" => "users",
        "action" => "profile"
    ),
    array(
        "pattern" => "logout",
        "controller" => "users",
        "action" => "logout"
    )
);

// add defined routes
foreach ($routes as $route) {
    $router->addRoute(new Framework\Router\Route\Simple($route));
}

// unset globals
unset($routes);
