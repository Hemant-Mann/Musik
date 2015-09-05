<?php

/**
 * Description of an Album
 *
 * @author Hemant Mann
 */
class Album extends Shared\Model {

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
     * @type text
     * @length 4
     * @index
     */
    protected $_year;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     */
    protected $_cover;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_genre_id;

}
