<?php

/**
 * Description of a song of an artist
 *
 * @author Hemant Mann
 */
class SongArtist extends Shared\Model {

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
     * @index
     */
    protected $_artist_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     */
    protected $_relation;

}
