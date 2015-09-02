<?php

/**
 * Record of a track/song in an album
 *
 * @author Hemant Mann
 */
class Track extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_album_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_song_id;

	/**
     * @column
     * @readwrite
     * @type integer
     * @length 4
     */
    protected $_trackno;    

}
