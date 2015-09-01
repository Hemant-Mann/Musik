<?php

namespace Framework\Cache {

    use Framework\Base as Base;
    use Framework\Cache\Exception as Exception;

    /**
     * It only overrides Base’s exception-generating methods to provide specific Exception subclasses for errors occurring within the  Cache\Driver subclasses.
     * There are some internal properties and methods that our cache driver class will need to maintain, in order to interact successfully with a Memcached server.
     */
    class Driver extends Base {

        public function initialize() {
            return $this;
        }

        protected function _getExceptionForImplementation($method) {
            return new Exception\Implementation("{$method} method not implemented");
        }

    }

} 