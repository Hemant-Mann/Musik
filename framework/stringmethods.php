<?php

namespace Framework {

    /**
     * Utility methods for working with the basic data types we ﬁnd in PHP
     */
    class StringMethods {

        /**
         *for the normalization of regular expression strings, so that the remaining methods can operate on them 
         * without ﬁrst having to check or normalize them.
         * @var type 
         */
        private static $_delimiter = "#";
        private static $_singular = array(
            "(matr)ices$" => "\\1ix",
            "(vert|ind)ices$" => "\\1ex",
            "^(ox)en" => "\\1",
            "(alias)es$" => "\\1",
            "([octop|vir])i$" => "\\1us",
            "(cris|ax|test)es$" => "\\1is",
            "(shoe)s$" => "\\1",
            "(o)es$" => "\\1",
            "(bus|campus)es$" => "\\1",
            "([m|l])ice$" => "\\1ouse",
            "(x|ch|ss|sh)es$" => "\\1",
            "(m)ovies$" => "\\1\\2ovie",
            "(s)eries$" => "\\1\\2eries",
            "([^aeiouy]|qu)ies$" => "\\1y",
            "([lr])ves$" => "\\1f",
            "(tive)s$" => "\\1",
            "(hive)s$" => "\\1",
            "([^f])ves$" => "\\1fe",
            "(^analy)ses$" => "\\1sis",
            "((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$" => "\\1\\2sis",
            "([ti])a$" => "\\1um",
            "(p)eople$" => "\\1\\2erson",
            "(m)en$" => "\\1an",
            "(s)tatuses$" => "\\1\\2tatus",
            "(c)hildren$" => "\\1\\2hild",
            "(n)ews$" => "\\1\\2ews",
            "([^u])s$" => "\\1"
        );
        private static $_plural = array(
            "^(ox)$" => "\\1\\2en",
            "([m|l])ouse$" => "\\1ice",
            "(matr|vert|ind)ix|ex$" => "\\1ices",
            "(x|ch|ss|sh)$" => "\\1es",
            "([^aeiouy]|qu)y$" => "\\1ies",
            "(hive)$" => "\\1s",
            "(?:([^f])fe|([lr])f)$" => "\\1\\2ves",
            "sis$" => "ses",
            "([ti])um$" => "\\1a",
            "(p)erson$" => "\\1eople",
            "(m)an$" => "\\1en",
            "(c)hild$" => "\\1hildren",
            "(buffal|tomat)o$" => "\\1\\2oes",
            "(bu|campu)s$" => "\\1\\2ses",
            "(alias|status|virus)" => "\\1es",
            "(octop)us$" => "\\1i",
            "(ax|cris|test)is$" => "\\1es",
            "s$" => "s",
            "$" => "s"
        );

        private function __construct() {
            // do nothing
        }

        private function __clone() {
            // do nothing
        }

        /**
         * For the normalization of regular expression strings, so that the remaining methods can operate on them
         * without ﬁrst having to check or normalize them. 
         * @param type $pattern
         * @return type
         */
        private static function _normalize($pattern) {
            return self::$_delimiter . trim($pattern, self::$_delimiter) . self::$_delimiter;
        }

        public static function getDelimiter() {
            return self::$_delimiter;
        }

        public static function setDelimiter($delimiter) {
            self::$_delimiter = $delimiter;
        }

        /**
         * Perform similarly to the preg_match_all() and preg_split() functions, but require less formal structure to the regular expressions,
         * and return a more predictable set of results
         * 
         * @param type $string
         * @param type $pattern
         * @return type return the ﬁrst captured substring, the entire substring match, or null
         */
        public static function match($string, $pattern) {
            preg_match_all(self::_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

            if (!empty($matches[1])) {
                return $matches[1];
            }

            if (!empty($matches[0])) {
                return $matches[0];
            }

            return null;
        }

        /**
         * Perform similarly to the preg_split() functions, but require less formal structure to the regular expressions,
         * and return a more predictable set of results
         * 
         * @param type $string
         * @param type $pattern
         * @param type $limit
         * @return type return the results of a call to the preg_split() function, after setting some ﬂags and normalizing the regular expression.
         */
        public static function split($string, $pattern, $limit = null) {
            $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
            return preg_split(self::_normalize($pattern), $string, $limit, $flags);
        }

        public static function sanitize($string, $mask) {
            if (is_array($mask)) {
                $parts = $mask;
            } else if (is_string($mask)) {
                $parts = str_split($mask);
            } else {
                return $string;
            }

            foreach ($parts as $part) {
                $normalized = self::_normalize("\\{$part}");
                $string = preg_replace(
                        "{$normalized}m", "\\{$part}", $string
                );
            }

            return $string;
        }

        public static function unique($string) {
            $unique = "";
            $parts = str_split($string);

            foreach ($parts as $part) {
                if (!strstr($unique, $part)) {
                    $unique .= $part;
                }
            }

            return $unique;
        }

        public static function indexOf($string, $substring, $offset = null) {
            $position = strpos($string, $substring, $offset);
            if (!is_int($position)) {
                return -1;
            }
            return $position;
        }

        public static function lastIndexOf($string, $substring, $offset = null) {
            $position = strrpos($string, $substring, $offset);
            if (!is_int($position)) {
                return -1;
            }
            return $position;
        }

        public static function singular($string) {
            $result = $string;

            foreach (self::$_singular as $rule => $replacement) {
                $rule = self::_normalize($rule);

                if (preg_match($rule, $string)) {
                    $result = preg_replace($rule, $replacement, $string);
                    break;
                }
            }

            return $result;
        }

        public static function plural($string) {
            $result = $string;

            foreach (self::$_plural as $rule => $replacement) {
                $rule = self::_normalize($rule);

                if (preg_match($rule, $string)) {
                    $result = preg_replace($rule, $replacement, $string);
                    break;
                }
            }

            return $result;
        }

        public static function datetime_to_text($datetime = "") {
            if ($datetime == '0000-00-00 00:00:00') {
                return "Not Specified";
            } else {
                $unixdatetme = strtotime($datetime);
                return strftime("%B %d %Y at %I:%M %p", $unixdatetme);
            }
        }

        public static function only_date($datetime = "") {
            if ($datetime == '0000-00-00 00:00:00') {
                return 'Not Specified';
            } else {
                $unixdatetme = strtotime($datetime);
                return strftime("%B %d %Y", $unixdatetme);
            }
        }

        public static function url($url) {
            $pattern = array(' ', '?', '.', ':', '\'', '/', '(', ')', ',', '&');
            $replace = array('-', '', '', '', '', '', '', '', '', '');
            return urlencode(str_replace($pattern, $replace, $url));
        }

    }

}