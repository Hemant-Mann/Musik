<?php

/**
 * A wrapper for different Lyrics Fetching Libraries
 *
 * @author Hemant Mann
 */

namespace Shared;
use Framework\Base as Base;

class Lyrics extends Base {

	/**
	 * Which Library to use
	 * @var string
	 * @readwrite
	 */
	protected $_library;

	/**
	 * Name of the track
	 * @var string
	 * @readwrite
	 */
	protected $_track;

	/**
	 * Name of the artist
	 * @var string
	 * @readwrite
	 */
	protected $_artist;

	public function __construct($options = array()) {
		parent::__construct($options);
	}

	public function findLyrics() {
		$api = "\Lyrics\ {$this->library}";
		$api = preg_replace('/\s+/', '', $api);

		try {
			return $api::findLyrics($this->track, $this->artist);	
		} catch (\Lyrics\Exceptions\Core $e) {
			echo $e->getCustomMessage();
		}
		
	}
}
