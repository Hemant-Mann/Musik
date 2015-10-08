<?php

/**
 * The Playlist Model
 *
 * @author Hemant Mann
 */
class Playlist extends Shared\Model {
    
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
    * @type integer
    * @index
    */
    protected $_strack_id;
}
