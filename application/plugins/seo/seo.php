<?php

/**
 * Improves Website SEO
 *
 * @author Faizan Ayubi
 */

use Framework\StringMethods as StringMethods;

class SEO {
    
    protected $_title;
    protected $_keywords;
    protected $_description;
    protected $_author;
    protected $_robots;
    protected $_photo;

    public function __construct($options) {
        if (!isset($options["title"])) {
            throw new Exception("Title not set");
        }

        $this->_title       = $options["title"];
        $this->_keywords    = $options["keywords"];
        $this->_description = $options["description"];
        $this->_author      = $options["author"];
        $this->_robots      = $options["robots"];
        $this->_photo       = $options["photo"];
    }

    public function __call($name, $arguments) {

        $getMatches = StringMethods::match($name, "^get([a-zA-Z0-9]+)$");
        if (sizeof($getMatches) > 0) {
            $normalized = lcfirst($getMatches[0]);
            $property = "_{$normalized}";

            if (property_exists($this, $property)) {
                return $this->$property;
            }
        }

        $setMatches = StringMethods::match($name, "^set([a-zA-Z0-9]+)$");
        if (sizeof($setMatches) > 0) {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";

            if (property_exists($this, $property)) {
                $this->$property = $arguments[0];
                return;
            }
        }
    }
    
}