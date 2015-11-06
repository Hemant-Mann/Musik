<?php

namespace YTDownloader;
use YTDownloader\Exceptions\Format;

class Conversion {
	private static $_supportedFormats = array(
		'audio' => array(
			'mp2', 'mp3', '3gp'
		),
		'video' => array(
			'avi', 'flv'
		)
	);

	private function __construct() {
		// do nothing
	}

	private function __clone() {
		// do nothing
	}

	public function To($fmt, $inFile, $outFile) {
		if (file_exists($outFile)) {
			return;
		}

		if (in_array($fmt, self::$_supportedFormats['audio']) || in_array($fmt, self::$_supportedFormats['video'])) {
			$cmd = "ffmpeg -i {$inFile} {$outFile}";
			exec($cmd); 
		} else {
			throw new Format("Unsupported format");
		}
	}
}