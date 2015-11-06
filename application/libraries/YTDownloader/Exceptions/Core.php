<?php

namespace YTDownloader\Exceptions;

class Core extends \Exception {
	public function __construct($msg) {
		parent::__construct($msg);
	}

	public function getCustomMsg() {
		return $this->message;
	}
}
