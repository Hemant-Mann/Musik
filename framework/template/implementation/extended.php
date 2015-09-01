<?php

namespace Framework\Template\Implementation {

    use Framework\Request as Request;
    use Framework\Registry as Registry;
    use Framework\Template as Template;
    use Framework\StringMethods as StringMethods;
    use Framework\RequestMethods as RequestMethods;

    class Extended extends Standard {

        /**
         * @readwrite
         */
        protected $_defaultPath = "application/views";

        /**
         * @readwrite
         */
        protected $_defaultKey = "_data";

        /**
         * @readwrite
         */
        protected $_index = 0;

        public function __construct($options = array()) {
            parent::__construct($options);

            $this->_map = array(
                "partial" => array(
                    "opener" => "{partial",
                    "closer" => "}",
                    "handler" => "_partial"
                ),
                "include" => array(
                    "opener" => "{include",
                    "closer" => "}",
                    "handler" => "_include"
                ),
                "ayield" => array(
                    "opener" => "{ayield",
                    "closer" => "}",
                    "handler" => "ayield"
                )
                    ) + $this->_map;

            $this->_map["statement"]["tags"] = array(
                "set" => array(
                    "isolated" => false,
                    "arguments" => "{key}",
                    "handler" => "set"
                ),
                "append" => array(
                    "isolated" => false,
                    "arguments" => "{key}",
                    "handler" => "append"
                ),
                "prepend" => array(
                    "isolated" => false,
                    "arguments" => "{key}",
                    "handler" => "prepend"
                )
                    ) + $this->_map["statement"]["tags"];
        }

        /**
         * Fetch a subtemplate and place it within the main template. The subtemplate should be processed at the same time 
         * as the main template, so that any logic can happen at the same time.
         * 
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _include($tree, $content) {
            $template = new Template(array(
                "implementation" => new self()
            ));

            $file = trim($tree["raw"]);
            $path = $this->defaultPath;
            $content = file_get_contents(APP_PATH . "/{$path}/{$file}");

            $template->parse($content);
            $index = $this->_index++;

            return "\$_anon = function(\$_data){
                " . $template->code . "
            };\$_text[] = \$_anon(\$_data);";
        }

        /**
         * Makes GET request to the URL and return the results to the template’s $_text array,
         * where it will be rendered to the final template output
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _partial($tree, $content) {
            $address = trim($tree["raw"], " /");

            if (StringMethods::indexOf($address, "http") != 0) {
                $host = RequestMethods::server("HTTP_HOST");
                $address = "http://{$host}/{$address}";
            }

            $request = new Request();
            $response = addslashes(trim($request->get($address)));

            return "\$_text[] = \"{$response}\";";
        }

        /**
         * Simply extracts the provided storage key from the language constructs.
         * @param type $tree
         * @return type
         */
        protected function _getKey($tree) {
            if (empty($tree["arguments"]["key"])) {
                return null;
            }

            return trim($tree["arguments"]["key"]);
        }

        /**
         * Offers the solution to storage that can be accessed from everywhere.
         * @param type $key
         * @param type $value
         */
        protected function _setValue($key, $value) {
            if (!empty($key)) {
                $data = Registry::get($this->defaultKey, array());
                $data[$key] = $value;

                Registry::set($this->defaultKey, $data);
            }
        }

        /**
         * Queries the stored data array for the value matching the provided $key.
         * @param type $key
         * @return string
         */
        protected function _getValue($key) {
            $data = Registry::get($this->defaultKey);

            if (isset($data[$key])) {
                return $data[$key];
            }

            return "";
        }

        /**
         * It is the first public construct handler we have used in any of our template implementations.
         * It modifies the value string from something resembling $_text =  "foo"; to something resembling foo.
         * @param type $key
         * @param type $value
         */
        public function set($key, $value) {
            if (StringMethods::indexOf($value, "\$_text") > -1) {
                $first = StringMethods::indexOf($value, "\"");
                $last = StringMethods::lastIndexOf($value, "\"");
                $value = stripslashes(substr($value, $first + 1, ($last - $first) - 1));
            }

            if (is_array($key)) {
                $key = $this->_getKey($key);
            }

            $this->_setValue($key, $value);
        }

        /**
         * Allow either a string or node tree to be supplied as the $key. If a node tree is provided, the _getKey() method will be used to extract the storage key needed.
         * @param type $key
         * @param type $value
         */
        public function append($key, $value) {
            if (is_array($key)) {
                $key = $this->_getKey($key);
            }

            $previous = $this->_getValue($key);
            $this->set($key, $previous . $value);
        }

        /**
         * Allow either a string or node tree to be supplied as the $key. If a node tree is provided, the _getKey() method will be used to extract the storage key needed.
         * @param type $key
         * @param type $value
         */
        public function prepend($key, $value) {
            if (is_array($key)) {
                $key = $this->_getKey($key);
            }

            $previous = $this->_getValue($key);
            $this->set($key, $value . $previous);
        }

        /**
         * Simplest of all the new constructs, and does pretty much the same thing as the standard implementation’s _literal() handler method.
         * @param type $tree
         * @param type $content
         * @return type
         */
        public function ayield($tree, $content) {
            $key = trim($tree["raw"]);
            $value = addslashes($this->_getValue($key));
            return "\$_text[] = \"{$value}\";";
        }

    }

}