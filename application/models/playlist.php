<?php

/**
 * Stores the info of a playlist created by user
 *
 * @author Hemant Mann
 */
class Playlist extends Shared\Model {
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @index
     */
    protected $_name;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @index
     */
    protected $_genre = "default";

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * @index
     */
    protected $_view = "private";
}
