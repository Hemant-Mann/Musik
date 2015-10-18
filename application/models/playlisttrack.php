<?php

/**
 * Stores the Tracks of a playlist
 *
 * @author Hemant Mann
 */
class PlaylistTrack extends Shared\Model {
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_playlist_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_strack_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_play_count;
}
