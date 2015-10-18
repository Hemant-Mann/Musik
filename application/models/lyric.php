<?php

/**
 * The Lyrics Model
 *
 * @author Hemant Mann
 */
class Lyric extends Shared\Model {
    
    /**
    * @column
    * @readwrite
    * @type text
    */
    protected $_lyrics;

    /**
    * @column
    * @readwrite
    * @type integer
    * @index
    */
    protected $_strack_id;
}
