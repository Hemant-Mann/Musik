<?php

/**
 * The Download Model
 *
 * @author Hemant Mann
 */
class Download extends Shared\Model {
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_count;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_strack_id;
}
