<?php

$dir = dirname(dirname(__FILE__));
spl_autoload_register(function($class) use ($dir) {
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    $file = "{$dir}/{$path}.php";
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
});
