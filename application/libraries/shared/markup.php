<?php

/**
 * Description of markup
 *
 * @author Faizan Ayubi
 */

namespace Shared {

    class Markup {

        public function __construct() {
            // do nothing
        }

        public function __clone() {
            // do nothing
        }

        public static function errors($array, $key, $separator = "<br />", $before = "<br />", $after = "") {
            if (isset($array[$key])) {
                return $before . join($separator, $array[$key]) . $after;
            }
            return "";
        }

        public static function models() {
            $model = array();
            $path = APP_PATH . "/application/models";
            $iterator = new \DirectoryIterator($path);

            foreach ($iterator as $item) {
                if (!$item->isDot() && $item->isFile()) {
                    array_push($model, substr($item->getFilename(), 0, -4));
                }
            }
            return $model;
        }

        public static function pagination($page) {
            if (strpos(URL, "?")) {
                $request = explode("?", URL);
                if (strpos($request[1], "&")) {
                    parse_str($request[1], $params);
                }

                $params["page"] = $page;
                return $request[0]."?".http_build_query($params);
            } else {
                $params["page"] = $page;
                return URL."?".http_build_query($params);
            }
            return "";
        }

        public static function log($message = "") {
            $logfile = self::logfile();
            $timestamp = strftime("%Y-%m-%d %H:%M:%S", time());
            $content = "[{$timestamp}]". $message . "\n";
            if (!file_put_contents($logfile, $content, FILE_APPEND)) {
                echo "Could not open log file for writing";
            }
        }

        public static function logfile() {
            $logfile = APP_PATH . "/logs/" . date("Y-m-d") . ".txt";
            $new = file_exists($logfile) ? false : true;

            if ($new) {
                exec('touch '. $logfile);
                chmod($logfile, 0755);
            }
            return $logfile;
        }

    }

}