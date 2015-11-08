<?php

/**
 * The post Model
 *
 * @author Faizan Ayubi
 */
class Post extends Shared\Model {
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type description
     */
    protected $_description;
}
