<?php

namespace Framework {

    /**
     * Utility methods for working with the basic data types we ﬁnd in PHP
     */
    class ArrayMethods {

        private function __construct() {
            # code...
        }

        private function __clone() {
            //do nothing
        }

        /**
         * Useful for converting a multidimensional array into a unidimensional array.
         * 
         * @param type $array
         * @param type $return
         * @return type
         */
        public static function flatten($array, $return = array()) {
            foreach ($array as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $return = self::flatten($value, $return);
                } else {
                    $return[] = $value;
                }
            }
            return $return;
        }

        public static function first($array) {
            if (sizeof($array) == 0) {
                return null;
            }

            $keys = array_keys($array);
            return $array[$keys[0]];
        }

        public static function last($array) {
            if (sizeof($array) == 0) {
                return null;
            }

            $keys = array_keys($array);
            return $array[$keys[sizeof($keys) - 1]];
        }

        public static function toObject($array) {
            $result = new \stdClass();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $result->{$key} = self::toObject($value);
                } else {
                    $result->{$key} = $value;
                }
            } return $result;
        }

        /**
         * Removes all values considered empty() and returns the resultant array
         * @param type $array
         * @return type the resultant array
         */
        public static function clean($array) {
            return array_filter($array, function ($item) {
                return !empty($item);
            });
        }

        /**
         * Returns an array, which contains all the items of the initial array, after they have been trimmed of all whitespace.
         * @param type $array
         * @return type array trimmed
         */
        public static function trim($array) {
            return array_map(function ($item) {
                return trim($item);
            }, $array);
        }

    }

}