<?php

/**
 * RequestMethods class is quite simple. It has methods for returning get/post/server variables, based on a key.
 * If that key is not present, the default value will be returned. We use these methods to return our posted form data to the controller.
 *
 * @author Faizan Ayubi
 */

namespace Framework {

    class RequestMethods {

        private function __construct() {
            // do nothing
        }
        
        private function __clone() {
            // do nothing
        }
        
        public static function get($key, $default = "") {
            if (!empty($_GET[$key])) {
                return $_GET[$key];
            }
            return $default;
        }

        public static function post($key, $default = "") {
            if (!empty($_POST[$key])) {
                return $_POST[$key];
            } return $default;
        }

        public static function server($key, $default = "") {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            } return $default;
        }

    }

}