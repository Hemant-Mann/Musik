<?php
namespace WebBot\lib\HTTP;
/**
 * HTTP Response class - grab HTTP GET and HEAD responses
 *
 * @package HTTP
 */
class Response {
	/**
	* HTTP response status code
	* @var int
	*/
	private $status_code;
	
	/**
	* HTTP response type
	* @var string
	*/
	private $type;
	
	/**
	* HTTP response body
	* @var string
	*/
	private $body;
	
	/**
	* HTTP response header
	* @var array
	*/
	private $header;

	/**
	* Successful fetch flag
	*
	* @var boolean
	*/
	public $success = false;

	/**
	* Init
	*
	* @param int $status_code Response status code for the page
	* @param string $type Response type
	* @param HTTP\Response $body Repsonse body
	* @param array $header HTTP Response header
	*/
	function __construct($status_code, $type, $body, $header) {
		$this->status_code = $status_code;
		$this->type = $type;
		$this->body = $body;
		$this->header = $header;

		$this->success = ($status_code == 200) ? true : false;
	}

	/**
	* getter
	* @return HTTP\Response StatusCode
	*/
	public function getStatusCode() {
		return $this->status_code;
	}

	/**
	* getter
	* @return HTTP\Response Body
	*/
	public function getBody() {
		return $this->body;
	}	
}
