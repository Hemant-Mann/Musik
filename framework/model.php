<?php

/**
 * Model class begins simply enough, with the properties we want getters/setters for, 
 * and the exception- generation override methods.
 *
 * @author Faizan Ayubi
 */

namespace Framework {

    use Framework\Base as Base;
    use Framework\Registry as Registry;
    use Framework\Inspector as Inspector;
    use Framework\StringMethods as StringMethods;
    use Framework\Model\Exception as Exception;

    class Model extends Base {

        /**
         * @readwrite
         */
        protected $_table;

        /**
         * @readwrite
         */
        protected $_connector;

        /**
         * @read
         */
        protected $_types = array("autonumber", "text", "integer", "decimal", "boolean", "datetime", "date", "time");

        /**
         * @read
         */
        protected $_validators = array(
            "required" => array(
                "handler" => "_validateRequired",
                "message" => "The {0} field is required"
            ),
            "alpha" => array(
                "handler" => "_validateAlpha",
                "message" => "The {0} field can only contain letters"
            ),
            "numeric" => array(
                "handler" => "_validateNumeric",
                "message" => "The {0} field can only contain numbers"
            ),
            "alphanumeric" => array(
                "handler" => "_validateAlphaNumeric",
                "message" => "The {0} field can only contain letters and numbers"
            ),
            "max" => array(
                "handler" => "_validateMax",
                "message" => "The {0} field must contain less than {2} characters"
            ),
            "min" => array(
                "handler" => "_validateMin",
                "message" => "The {0} field must contain more than {2} characters"
            )
        );

        /**
         * @read
         */
        protected $_errors = array();
        protected $_columns;
        protected $_primary;

        public function __construct($options = array()) {
            parent::__construct($options);
            $this->load();
        }

        /**
         * Determines the model’s primary column and checks to see whether it is not empty.
         * This tells us whether the primary key has been provided,
         * which gives us a viable means of finding the intended record. If the primary key class property is empty,
         * we assume this model instance is intended for the creation of a new record, and do nothing further.
         * 
         * @throws Exception\Primary
         */
        public function load() {
            $primary = $this->primaryColumn;
            $raw = $primary["raw"];
            $name = $primary["name"];
            if (!empty($this->$raw)) {
                $previous = $this->connector->query()->from($this->table)->where("{$name} = ?", $this->$raw)->first();
                if ($previous == null) {
                    throw new Exception\Primary("Primary key value invalid");
                } foreach ($previous as $key => $value) {
                    $prop = "_{$key}";
                    if (!empty($previous->$key) && !isset($this->$prop)) {
                        $this->$key = $previous->$key;
                    }
                }
            }
        }

        /**
         * Creates a query instance, and targets the table related to the Model class. 
         * @return type
         */
        public function save() {
            $primary = $this->primaryColumn;
            $raw = $primary["raw"];
            $name = $primary["name"];
            $query = $this->connector->query()->from($this->table);
            if (!empty($this->$raw)) {
                $query->where("{$name} = ?", $this->$raw);
            } $data = array();
            foreach ($this->columns as $key => $column) {
                if (!$column["read"]) {
                    $prop = $column["raw"];
                    $data[$key] = $this->$prop;
                    continue;
                } if ($column != $this->primaryColumn && $column) {
                    $method = "get" . ucfirst($key);
                    $data[$key] = $this->$method();
                    continue;
                }
            } $result = $query->save($data);
            if ($result > 0) {
                $this->$raw = $result;
            } return $result;
        }

        /**
         * Creates a query object, only if the primary key property value is not empty
         * @return type
         */
        public function delete() {
            $primary = $this->primaryColumn;
            $raw = $primary["raw"];
            $name = $primary["name"];
            if (!empty($this->$raw)) {
                return $this->connector->query()->from($this->table)->where("{$name} = ?", $this->$raw)->delete();
            }
        }

        /**
         * Creates a query object, only if the primary key property value is not empty but statistacally
         * @param type $where
         * @return type
         */
        public static function deleteAll($where = array()) {
            $instance = new static();
            $query = $instance->connector->query()->from($instance->table);
            foreach ($where as $clause => $value) {
                $query->where($clause, $value);
            }
            return $query->delete();
        }

        /**
         * Simple, static wrapper method for the protected _all() method
         * @param type $where where fields in array format
         * @param type $fields
         * @param type $order
         * @param type $direction asc(for ascending) or desc(for descending)
         * @param type $limit
         * @param type $page
         * @return type
         */
        public static function all($where = array(), $fields = array("*"), $order = null, $direction = null, $limit = null, $page = null) {
            $model = new static();
            return $model->_all($where, $fields, $order, $direction, $limit, $page);
        }

        /**
         * Creates a query, taking into account the various filters and ﬂags, to return all matching records.
         * @param type $where
         * @param type $fields
         * @param type $order
         * @param type $direction
         * @param type $limit
         * @param type $page
         * @return \Framework\class
         */
        protected function _all($where = array(), $fields = array("*"), $order = null, $direction = null, $limit = null, $page = null) {
            $query = $this->connector->query()->from($this->table, $fields);
            foreach ($where as $clause => $value) {
                $query->where($clause, $value);
            } if ($order != null) {
                $query->order($order, $direction);
            } if ($limit != null) {
                $query->limit($limit, $page);
            } $rows = array();
            $class = get_class($this);
            foreach ($query->all() as $row) {
                $rows[] = new $class($row);
            }
            return $rows;
        }

        /**
         * Simple, static wrapper method to a protected instance method _first
         * @param type $where
         * @param type $fields
         * @param type $order
         * @param type $direction
         * @return type
         */
        public static function first($where = array(), $fields = array("*"), $order = null, $direction = null) {
            $model = new static();
            return $model->_first($where, $fields, $order, $direction);
        }

        /**
         * Simply returns the first matched record
         * @param type $where
         * @param type $fields
         * @param type $order
         * @param type $direction
         * @return \Framework\class
         */
        protected function _first($where = array(), $fields = array("*"), $order = null, $direction = null) {
            $query = $this->connector->query()->from($this->table, $fields);
            foreach ($where as $clause => $value) {
                $query->where($clause, $value);
            } if ($order != null) {
                $query->order($order, $direction);
            } $first = $query->first();
            $class = get_class($this);
            if ($first) {
                return new $class($query->first());
            } return null;
        }

        /**
         * Simple, static wrapper method to a protected instance method _count
         * @param type $where
         * @return type
         */
        public static function count($where = array()) {
            $model = new static();
            return $model->_count($where);
        }

        /**
         * Returns a count of the matched records.
         * @param type $where
         * @return type
         */
        protected function _count($where = array()) {
            $query = $this->connector->query()->from($this->table);
            foreach ($where as $clause => $value) {
                $query->where($clause, $value);
            } return $query->count();
        }

        public function _getExceptionForImplementation($method) {
            return new Exception\Implementation("{$method} method not implemented");
        }

        /**
         * Return a user-defined table name or else default to the singular form of the current Model’s class name
         * @return type
         */
        public function getTable() {
            if (empty($this->_table)) {
                $this->_table = strtolower(StringMethods::plural(get_class($this)));
            } return $this->_table;
        }

        /**
         * Return the contents of the $_connector property, a connector instance stored in the Registry class,
         * or raise a Model\Exception\Connector.
         * @return type
         * @throws Exception\Connector
         */
        public function getConnector() {
            if (empty($this->_connector)) {
                $database = Registry::get("database");
                if (!$database) {
                    throw new Exception\Connector("No connector availible");
                } $this->_connector = $database->initialize();
            } return $this->_connector;
        }

        /**
         * Returns an associative array of column data.
         * @return type
         * @throws Exception\Type
         * @throws Exception\Primary
         */
        public function getColumns() {
            if (empty($_columns)) {
                $primaries = 0;
                $columns = array();
                $class = get_class($this);
                $types = $this->types;
                $inspector = new Inspector($this);
                $properties = $inspector->getClassProperties();
                $first = function($array, $key) {
                    if (!empty($array[$key]) && sizeof($array[$key]) == 1) {
                        return $array[$key][0];
                    }
                    return null;
                };
                foreach ($properties as $property) {
                    $propertyMeta = $inspector->getPropertyMeta($property);
                    if (!empty($propertyMeta["@column"])) {
                        $name = preg_replace("#^_#", "", $property);
                        $primary = !empty($propertyMeta["@primary"]);
                        $type = $first($propertyMeta, "@type");
                        $length = $first($propertyMeta, "@length");
                        $index = !empty($propertyMeta["@index"]);
                        $readwrite = !empty($propertyMeta["@readwrite"]);
                        $read = !empty($propertyMeta["@read"]) || $readwrite;
                        $write = !empty($propertyMeta["@write"]) || $readwrite;
                        $validate = !empty($propertyMeta["@validate"]) ? $propertyMeta["@validate"] : false;
                        $label = $first($propertyMeta, "@label");
                        if (!in_array($type, $types)) {
                            throw new Exception\Type("{$type} is not a valid type");
                        }
                        if ($primary) {
                            $primaries++;
                        }
                        $columns[$name] = array(
                            "raw" => $property,
                            "name" => $name,
                            "primary" => $primary,
                            "type" => $type,
                            "length" => $length,
                            "index" => $index,
                            "read" => $read,
                            "write" => $write,
                            "validate" => $validate,
                            "label" => $label
                        );
                    }
                } if ($primaries !== 1) {
                    throw new Exception\Primary("{$class} must have exactly one @primary column");
                }
                $this->_columns = $columns;
            } return $this->_columns;
        }

        /**
         * Returns a column by name.
         * @param type $name
         * @return type
         */
        public function getColumn($name) {
            if (!empty($this->_columns[$name])) {
                return $this->_columns[$name];
            } return null;
        }

        /**
         * Loops through the columns, returning the one marked as primary.
         * @return type
         */
        public function getPrimaryColumn() {
            if (!isset($this->_primary)) {
                $primary;
                foreach ($this->columns as $column) {
                    if ($column["primary"]) {
                        $primary = $column;
                        break;
                    }
                }
                $this->_primary = $primary;
            } return $this->_primary;
        }

        protected function _validateRequired($value) {
            return !empty($value);
        }

        protected function _validateAlpha($value) {
            return StringMethods::match($value, "#^([a-zA-Z]+)$#");
        }

        protected function _validateNumeric($value) {
            return StringMethods::match($value, "#^([0-9]+)$#");
        }

        protected function _validateAlphaNumeric($value) {
            return StringMethods::match($value, "#^([a-zA-Z0-9]+)$#");
        }

        protected function _validateMax($value, $number) {
            return strlen($value) <= (int) $number;
        }

        protected function _validateMin($value, $number) {
            return strlen($value) >= (int) $number;
        }

        /**
         * Begins by getting a list of columns and iterating over that list. For each column, we determine whether validation should occur.
         * We then split the @validate metadata into a list of validation conditions. If a condition has arguments, we extract the arguments.
         * We then run each validation method on the column data and generate error messages for those validation conditions that failed.
         * 
         * @return type
         * @throws Exception\Validation
         */
        public function validate() {
            $this->_errors = array();
            foreach ($this->columns as $column) {
                if ($column["validate"]) {
                    $pattern = "#[a-z]+\(([a-zA-Z0-9, ]+)\)#";

                    $raw = $column["raw"];
                    $name = $column["name"];
                    $validators = $column["validate"];
                    $label = $column["label"];

                    $defined = $this->getValidators();

                    foreach ($validators as $validator) {
                        $function = $validator;
                        $arguments = array(
                            $this->$raw
                        );

                        $match = StringMethods::match($validator, $pattern);

                        if (count($match) > 0) {
                            $matches = StringMethods::split($match[0], ",\s*");
                            $arguments = array_merge($arguments, $matches);
                            $offset = StringMethods::indexOf($validator, "(");
                            $function = substr($validator, 0, $offset);
                        }

                        if (!isset($defined[$function])) {
                            throw new Exception\Validation("The {$function} validator is not defined");
                        }

                        $template = $defined[$function];

                        if (!call_user_func_array(array($this, $template["handler"]), $arguments)) {
                            $replacements = array_merge(array(
                                $label ? $label : $raw
                                    ), $arguments);

                            $message = $template["message"];

                            foreach ($replacements as $i => $replacement) {
                                $message = str_replace("{{$i}}", $replacement, $message);
                            }

                            if (!isset($this->_errors[$name])) {
                                $this->_errors[$name] = array();
                            }

                            $this->_errors[$name][] = $message;
                        }
                    }
                }
            }

            return !sizeof($this->errors);
        }

        public function getJsonData() {
            $this->removeProperty($this);
            $var = get_object_vars($this);
            foreach($var as &$value){
                if(is_object($value) && method_exists($value,'getJsonData')){
                    $value = $value->getJsonData();
                }
            }
            return $var;
        }
        
        public function removeProperty() {
            unset($this->_connector);
            unset($this->_table);
            unset($this->_types);
            unset($this->_columns);
            unset($this->_primary);
            unset($this->_validators);
            unset($this->_errors);
        }
        

    }

}