<?php

namespace Framework\Router\Route {

    use Framework\Router as Router;
    use Framework\ArrayMethods as ArrayMethods;

    /**
     * Responsible for matching Routes format of :property
     */
    class Simple extends Router\Route {

        /**
         * Converts substrings matching the format of :property to regular expression wildcards
         * @param type $url
         * @return boolean
         */
        public function matches($url) {
            $pattern = $this->pattern;
            // get keys
            preg_match_all("#:([a-zA-Z0-9]+)#", $pattern, $keys);
            
            if (sizeof($keys) && sizeof($keys[0]) && sizeof($keys[1])) {
                $keys = $keys[1];
            } else {
                // no keys in the pattern, return a simple match
                return preg_match("#^{$pattern}$#", $url);
            }
            // normalize route pattern
            $pattern = preg_replace("#(:[a-zA-Z0-9]+)#", "([a-zA-Z0-9-+_ ]+)", $pattern);
            
            // check values
            preg_match_all("#^{$pattern}$#", $url, $values);

            if (sizeof($values) && sizeof($values[0]) && sizeof($values[1])) {
                // unset the matched url
                unset($values[0]);
                
                // values found, modify parameters and return
                $derived = array_combine($keys, ArrayMethods::flatten($values));
                $this->parameters = array_merge($this->parameters, $derived);
                return true;
            }
            return false;
        }
    }

}