<?php

/**
 * Description of songs
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Songs extends Admin {

	/**
	 * @before _secure, changeLayout
	 */
	public function artist($name = NULL, $id = NULL) {
		// find all the songs by the artist
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function edit($id = NULL) {

	}

}