<?php

namespace Framework {

    use Framework\ArrayMethods as ArrayMethods;
    use Framework\StringMethods as StringMethods;

    /**
     * Allows us to inspect these Doc Comments, and return the relevant key/value pairs for us to use elsewhere
     * The public methods of our Inspector class utilize all of our internal methods to return the Doc Comment string values,
     * parse them into associative arrays, and return usable metadata. Since classes cannot change at runtime, 
     * all of the public methods cache the results of their ﬁrst execution within the internal properties. 
     * Our public methods allow us to list the methods and properties of a class. They also allow us to return the key/value metadata of the class, 
     * named methods, and named properties, without the methods or properties needing to be public.
     */
    class Inspector {

        protected $_class;
        protected $_meta = array(
            "class" => array(),
            "properties" => array(),
            "methods" => array()
        );
        protected $_properties = array();
        protected $_methods = array();

        public function __construct($class) {
            $this->_class = $class;
        }

        /**
         * Get the string values of Doc Comments of a class.
         * @return type
         */
        protected function _getClassComment() {
            $reflection = new \ReflectionClass($this->_class);
            return $reflection->getDocComment();
        }

        /**
         * Get a list of the properties of a class.
         * @return type
         */
        protected function _getClassProperties() {
            $reflection = new \ReflectionClass($this->_class);
            return $reflection->getProperties();
        }

        /**
         * Get a list of the methods of a class.
         * @return type
         */
        protected function _getClassMethods() {
            $reflection = new \ReflectionClass($this->_class);
            return $reflection->getMethods();
        }

        /**
         * Get the string values of Doc Comments of a property of a class.
         * @param type $property
         * @return type
         */
        protected function _getPropertyComment($property) {
            $reflection = new \ReflectionProperty($this->_class, $property);
            return $reflection->getDocComment();
        }

        /**
         * Get the string values of Doc Comments of a method of a class.
         * @param type $method
         * @return type
         */
        protected function _getMethodComment($method) {
            $reflection = new \ReflectionMethod($this->_class, $method);
            return $reflection->getDocComment();
        }

        /**
         * Uses a fairly simple regular expression to match key/value pairs within the Doc Comment string returned by any of our _get…Meta() methods.
         * It does this using the StringMethods::match() method. It loops through all the matches, splitting them by key/value. If it ﬁnds no value component,
         * it sets the key to a value of true. This is useful for ﬂag keys such as @readwrite or @once.
         * If it ﬁnds a value component, it splits the value by, and assigns an array of value parts to the key.
         * Finally, it returns the key/value(s) associative array.
         * 
         * @param type $comment string
         * @return type associative array
         */
        protected function _parse($comment) {
            $meta = array();
            $pattern = "(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)";
            $matches = StringMethods::match($comment, $pattern);

            if ($matches != null) {
                foreach ($matches as $match) {
                    $parts = ArrayMethods::clean(
                                    ArrayMethods::trim(
                                            StringMethods::split($match, "[\s]", 2)
                                    )
                    );

                    $meta[$parts[0]] = true;

                    if (sizeof($parts) > 1) {
                        $meta[$parts[0]] = ArrayMethods::clean(
                                        ArrayMethods::trim(
                                                StringMethods::split($parts[1], ",")
                                        )
                        );
                    }
                }
            }

            return $meta;
        }

        /**
         * Used to return the parsed Doc Comment string data of class
         * @return type
         */
        public function getClassMeta() {
            if (!isset($_meta["class"])) {
                $comment = $this->_getClassComment();

                if (!empty($comment)) {
                    $_meta["class"] = $this->_parse($comment);
                } else {
                    $_meta["class"] = null;
                }
            }

            return $_meta["class"];
        }

        public function getClassProperties() {
            if (!isset($_properties)) {
                $properties = $this->_getClassProperties();

                foreach ($properties as $property) {
                    $_properties[] = $property->getName();
                }
            }

            return $_properties;
        }

        public function getClassMethods() {
            if (!isset($_methods)) {
                $methods = $this->_getClassMethods();

                foreach ($methods as $method) {
                    $_methods[] = $method->getName();
                }
            }

            return $_properties;
        }

        public function getPropertyMeta($property) {
            if (!isset($_meta["properties"][$property])) {
                $comment = $this->_getPropertyComment($property);

                if (!empty($comment)) {
                    $_meta["properties"][$property] = $this->_parse($comment);
                } else {
                    $_meta["properties"][$property] = null;
                }
            }

            return $_meta["properties"][$property];
        }

        public function getMethodMeta($method) {
            if (!isset($_meta["actions"][$method])) {
                $comment = $this->_getMethodComment($method);

                if (!empty($comment)) {
                    $_meta["methods"][$method] = $this->_parse($comment);
                } else {
                    $_meta["methods"][$method] = null;
                }
            }

            return $_meta["methods"][$method];
        }

    }

}