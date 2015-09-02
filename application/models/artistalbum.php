<?php

/**
 * Table to keep record of an Album by Artist
 *
 * @author Hemant Mann
 */
class ArtistAlbum extends Shared\Model {

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
     * @type integer
     * @index
     */
    protected $_album_id;

}
