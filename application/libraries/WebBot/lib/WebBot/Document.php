<?php
namespace WebBot\lib\WebBot;

class Document {
	/**
    * Store Document Response object
    * @var \WebBot\lib\HTTP\Response object
    */
	private $response_obj;
	
	/**
    * Store Document ID
    * @var int|string
    */
	public $id;

	/**
    * Store Document URL
    * @var string
    */
	public $uri;
	
	public function __construct($response, $id, $uri) {
		$this->response_obj = $response;
		$this->id = $id;
		$this->uri = $uri;
	}

	/**
    * getter
    * @return \WebBot\lib\HTTP\Response object
    */
	public function getHttpResponse() {
		return $this->response_obj;
	}

	/**
	* Test if value/pattern exists in reponse data
	*
	* @param mixed $value (value or regex pattern, if regex pattern donot
	* pattern modifiers and use regex delims '/', ex: '/pattern/')
	* @param boolean $case_insensitive
	* @return boolean
	*/
	public function test($value, $case_insensitive = true) {
		if(preg_match('#^\/.*\/$#', $value)) { // regex pattern
			return preg_match($value. 'Usm'. ($case_insensitive ? 'i': ''), $this->getHttpResponse()->getBody());
		} else { // no regex, use string position
			return call_user_func(($case_insensitive ? 'stripos' : 'strpos'), $this->getHttpResponse()->getBody(), $value);
		}

		return false;
	}

	/**
	* Find a given pattern using preg_match or str_pos
	*
	* @return array|string|boolean
	*/
	public function find($value, $read_length_or_str = 0, $case_insensitive = true) {
		if($this->test($value, $case_insensitive)) {
			if(preg_match('#^\/.*\/$#', $value)) { // regex pattern
				preg_match_all($value, $this->getHttpResponse()->getBody(), $m);
				return $m;
			} else { // no regex, use string position
				$pos = call_user_func(($case_insensitive ? 'stripos' : 'strpos'), $this->getHttpResponse()->getBody(), $value);

				if(is_string($read_length_or_str)) {
					$pos += strlen(value); // move position length of value
					$pos_end = call_user_func(($case_insensitive ? 'stripos' : 'strpos' ), $this->getHttpResponse()->getBody(), $read_length_or_str);

					echo "start: $pos, end: $pos_end<br />";
					if($pos_end !== false && $pos_end > $pos) {
						$diff = $pos_end - $pos;
						return substr($this->getHttpResponse()->getBody(), $pos, $diff);
					}
				} else { // int read length
					$read_length = (int) $read_length_or_str;

					return $read_length < 1 ? substr($this->getHttpResponse()->getBody(), $pos) : substr($this->getHttpResponse()->getBody(), $pos, $read_length);
				}
			}
		} else {
			return false;
		} 
	}

	// Function to return XPath object
	public function returnXPathObject() {
		$xmlPageDom = new \DomDocument(); // Instantiating a new DomDocument object
		@$xmlPageDom->loadHTML($this->getHttpResponse()->getBody()); // Loading the HTML from downloaded page
		$xmlPageXPath = new \DOMXPath($xmlPageDom); // Instantiating new XPath DOM object
		return $xmlPageXPath; // Returning XPath object
	}
}

?>