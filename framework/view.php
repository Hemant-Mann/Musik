<?php

/**
 * Class to instantiate view of controller/action
 *
 * @author Faizan Ayubi
 */

namespace Framework {

    use Framework\Base as Base;
    use Framework\Events as Events;
    use Framework\Template as Template;
    use Framework\View\Exception as Exception;

    class View extends Base {

        /**
         * @readwrite
         */
        protected $_file;

        /**
         * @readwrite
         */
        protected $_data;

        /**
         * @read
         */
        protected $_template;

        /**
         * Creates a Template instance, which it will later use to parse the view templates.
         * it has methods for storing, retrieving, and erasing key/value pairs of template data, which it provides to the template parser.
         * @param type $options
         */
        public function __construct($options = array()) {
            parent::__construct($options);

            Events::fire("framework.view.construct.before", array($this->file));

            $this->_template = new Template(array(
                "implementation" => new Template\Implementation\Extended()
            ));

            Events::fire("framework.view.construct.after", array($this->file, $this->template));
        }

        public function _getExceptionForImplementation($method) {
            return new Exception\Implementation("{$method} method not implemented");
        }

        /**
         * Parses the HTML file for the action
         * @return string
         */
        public function render() {
            Events::fire("framework.view.render.before", array($this->file));

            if (!file_exists($this->file)) {
                return "";
            }

            return $this
                            ->template
                            ->parse(file_get_contents($this->file))
                            ->process($this->data);
        }

        /**
         * Get the variable defined in View instance
         * @param type $key
         * @param type $default
         * @return type
         */
        public function get($key, $default = "") {
            if (isset($this->data[$key])) {
                return $this->data[$key];
            }
            return $default;
        }

        protected function _set($key, $value) {
            if (!is_string($key) && !is_numeric($key)) {
                throw new Exception\Data("Key must be a string or a number");
            }

            $data = $this->data;

            if (!$data) {
                $data = array();
            }

            $data[$key] = $value;
            $this->data = $data;
        }

        /**
         * Set the variable defined in View instance
         * @param type $key
         * @param type $value
         * @return \Framework\View
         */
        public function set($key, $value = null) {
            if (is_array($key)) {
                foreach ($key as $_key => $value) {
                    $this->_set($_key, $value);
                }
                return $this;
            }

            $this->_set($key, $value);
            return $this;
        }

        /**
         * Erase the variable defined in View instance
         * @param type $key
         * @return \Framework\View
         */
        public function erase($key) {
            unset($this->data[$key]);
            return $this;
        }

    }

}