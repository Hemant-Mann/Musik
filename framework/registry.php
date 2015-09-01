<?php

namespace Framework {

    /**
     * The Registry is a Singleton, used to store instance of other “normal” classes.
     */
    class Registry {

        private static $_instances = array();

        private function __construct() {
            // do nothing 
        }
        
        private function __clone() {
            // do nothing
        }
        
        /**
         * Searches the private storage for an instance with a matching key. 
         * If it finds an instance, it will return it, or default to the value supplied with the $default parameter.
         * @param type $key
         * @param type $default
         * @return type
         */
        public static function get($key, $default = null) {
            if (isset(self::$_instances[$key])) {
                return self::$_instances[$key];
            }
            return $default;
        }
        
        /**
         * Used to “store” an instance with a specified key in the registry’s private storage
         * @param type $key
         * @param type $instance
         */
        public static function set($key, $instance = null) {
            self::$_instances[$key] = $instance;
        }

        /**
         * Useful for removing an instance at a certain key.
         * @param type $key
         */
        public static function erase($key) {
            unset(self::$_instances[$key]);
        }

    }
    
}