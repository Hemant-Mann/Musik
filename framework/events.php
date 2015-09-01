<?php

/**
 * Class to manage the event listeners, and emit events.
 *
 * @author Faizan Ayubi
 */

namespace Framework {

    class Events {

        private static $_callbacks = array();

        private function __construct() {
            // do nothing
        }

        private function __clone() {
            // do nothing
        }

        /**
         * Adds the provided $callback to the $_callbacks array, so that it can be executed when any $type  events happen.
         * @param type $type
         * @param type $callback
         */
        public static function add($type, $callback) {
            if (empty(self::$_callbacks[$type])) {
                self::$_callbacks[$type] = array();
            }

            self::$_callbacks[$type][] = $callback;
        }

        /**
         * Emits/triggers an event. If you provide an optional $parameters array, 
         * this array will be available to all the callbacks executed.
         * @param type $type
         * @param type $parameters
         */
        public static function fire($type, $parameters = null) {
            if (!empty(self::$_callbacks[$type])) {
                foreach (self::$_callbacks[$type] as $callback) {
                    call_user_func_array($callback, $parameters);
                }
            }
        }

        /**
         * Simply removes the stored $callback from the $_callbacks array.
         * @param type $type
         * @param type $callback
         */
        public static function remove($type, $callback) {
            if (!empty(self::$_callbacks[$type])) {
                foreach (self::$_callbacks[$type] as $i => $found) {
                    if ($callback == $found) {
                        unset(self::$_callbacks[$type][$i]);
                    }
                }
            }
        }

    }

}