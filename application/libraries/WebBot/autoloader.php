<?php
namespace WebBot;

define("LIB_PATH", dirname(dirname(__FILE__)));

spl_autoload_register(function($class) {
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    $file = LIB_PATH."/{$path}.php";
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
});