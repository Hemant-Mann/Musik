<?php
namespace Lyrics\Exceptions;

class Core extends \Exception {
	public function __construct($message) {
		parent::__construct($message);
	}

	public function getCustomMessage() {
		return $this->message;
	}
}
