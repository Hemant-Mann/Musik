<?php

/**
 * Contains similar code of all models and some helpful methods
 *
 * @author Faizan Ayubi
 */

namespace Shared {

    class Model extends \Framework\Model {

        /**
         * @column
         * @readwrite
         * @primary
         * @type autonumber
         */
        protected $_id;

        /**
         * @column
         * @readwrite
         * @type boolean
         * @index
         */
        protected $_live;

        /**
         * @column
         * @readwrite
         * @type boolean
         * @index
         */
        protected $_deleted;

        /**
         * @column
         * @readwrite
         * @type datetime
         */
        protected $_created;

        /**
         * @column
         * @readwrite
         * @type datetime
         */
        protected $_modified;

        /**
         * Every time a row is created these fields should be populated with default values.
         */
        public function save() {
            $primary = $this->getPrimaryColumn();
            $raw = $primary["raw"];
            if (empty($this-> $raw)) {
                $this->setCreated(date("Y-m-d H:i:s"));
                $this->setDeleted(false);
                $this->setLive(true);
            }
            $this->setModified(date("Y-m-d H:i:s"));
            parent::save();
        }

    }

}