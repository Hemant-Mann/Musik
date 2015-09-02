<?php

/**
 * Description of an song
 *
 * @author Hemant Mann
 */
class Song extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type text
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type time
     */
    protected $_playtime;

    /**
     * @column
     * @readwrite
     * @type integer
     * @length 5
     */
    protected $_bitrate;

    /**
     * @column
     * @readwrite
     * @type integer
     */
    protected $_file_id;

}
