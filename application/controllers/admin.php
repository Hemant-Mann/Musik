<?php

/**
 * The admin controller which has highest privilege to manage the website
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;

class Admin extends Users {

    /**
     * @readwrite
     */
    protected $_member;
	
    protected function sync($model) {
        $this->noview();
        $db = Registry::get("database");
        $db->sync(new $model);
    }
}
