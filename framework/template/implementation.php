<?php

/**
 * Main Template class for template files, will be used as a parent for many subclasses
 *
 * @author Faizan Ayubi
 */

namespace Framework\Template {

    use Framework\Base as Base;
    use Framework\StringMethods as StringMethods;
    use Framework\Template\Exception as Exception;

    class Implementation extends Base {

        /**
         * Takes a $node array and determines the correct handler method to execute.
         * @param type $node
         * @return type
         */
        protected function _handler($node) {
            if (empty($node["delimiter"])) {
                return null;
            }

            if (!empty($node["tag"])) {
                return $this->_map[$node["delimiter"]]["tags"][$node["tag"]]["handler"];
            }

            return $this->_map[$node["delimiter"]]["handler"];
        }

        /**
         * The handle() method uses the _handler() method to get the correct handler method, 
         * and executes it, throwing a Template\Exception\Implementation exception 
         * if there was a problem executing the statement’s handler.
         * 
         * @param type $node
         * @param type $content
         * @return type
         * @throws Exception\Implementation
         */
        public function handle($node, $content) {
            try {
                $handler = $this->_handler($node);
                return call_user_func_array(array($this, $handler), array($node, $content));
            } catch (\Exception $e) {
                throw new Exception\Implementation();
            }
        }

        /**
         * Evaluates a $source string to determine if it matches a tag or statement.
         * @param type $source
         * @return type
         */
        public function match($source) {
            $type = null;
            $delimiter = null;

            foreach ($this->_map as $_delimiter => $_type) {
                if (!$delimiter || StringMethods::indexOf($source, $type["opener"]) == -1) {
                    $delimiter = $_delimiter;
                    $type = $_type;
                }

                $indexOf = StringMethods::indexOf($source, $_type["opener"]);

                if ($indexOf > -1) {
                    if (StringMethods::indexOf($source, $type["opener"]) > $indexOf) {
                        $delimiter = $_delimiter;
                        $type = $_type;
                    }
                }
            }

            if ($type == null) {
                return null;
            }

            return array(
                "type" => $type,
                "delimiter" => $delimiter
            );
        }

    }

}