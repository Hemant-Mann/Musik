<?php
namespace WebBot\lib\HTTP;
use WebBot\lib\HTTP\Response as Response;
/**
 * HTTP Request class - execute HTTP GET and HEAD requests
 *
 * @package HTTP
 */
class Request {
	/**
	 * User Agent
	 *
	 * @var string
	*/
	public static $user_agent = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';

	/**
	 * Format timeout in seconds, if no timeout use default timeout
	 *
	 * @param int|float $timeout (seconds)
	 * @return int|float
	*/
	private static function __formatTimeout($timeout = 0) {
		$timeout = (float) $timeout; // format timeout value

		if ($timeout < 0.1) {
			$timeout = 60; // default timeout
		}

		return $timeout;
	}

	/**
	 * Parse HTTP response
	 *
	 * @param string $body
	 * @param array $headers
	 * @return HTTP\Response object
	*/
	private static function __parseResponse($body, $headers) {
		$content_type = '';

		if (is_array($headers)) {
			$header = array_pop($headers);	// will return an array of headers
			
			// Get status code and content type of the page
			$status_code = $header['http_code'];
			$content_type = $header['Content-Type'];	
		}

		return new Response($status_code, $content_type, $body, $header);
	}

	/**
	 * Execute HTTP GET request using the cURL library. We can setup various options for executing the request
	 *
	 * @param string $url
	 * @param int|float $timeout(seconds)
	 * @return Response object
	*/
	public static function get($url, $timeout = 0) {
		$ch = curl_init();	// initializing cURL session
		curl_setopt($ch, CURLOPT_URL, $url);	// executing cURL session
		curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);	// follow any "Location" header
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);	// include the header in the output
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// returning transfer as a string
		$response = curl_exec($ch);

		// get size of header string
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);

		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		$headers = self::headers($header);
		// echo "<pre>". print_r($headers, true). "</pre>";

		return self::__parseResponse($body, $headers);
	}

	/**
	 * Execute HTTP HEAD request which is basically a GET request but with no body
	 *
	 * @param string $url
	 * @param int|float $timeout
	 * @return Response object
	*/
	public static function head($url, $timeout = 0) {
		$ch = curl_init();	// initializing cURL session
		curl_setopt($ch, CURLOPT_URL, $url);	// executing cURL session
		curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);	// follow any "Location" header
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);	// include the header in the output
		curl_setopt($ch, CURLOPT_NOBODY, true);	// Because we need only the headers
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// returning transfer as a string
		$response = curl_exec($ch);

		// get size of header string
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);

		$header = substr($response, 0, $header_size);
		$headers = self::headers($header);

		return self::__parseResponse('', $headers);
	}

	/**
	 * Returns an associative array of headers from the header string
	 * @param string $headerContent Contains the different headers separated by \r\n
	 * @return array
	 */
	private static function headers($headerContent) {
		$headers = array();

	    // Split the string on every "double" new line.
	    $arrRequests = explode("\r\n\r\n", $headerContent);

	    // Loop of response headers. The "count() -1" is to 
	    //avoid an empty row for the extra line break before the body of the response.
	    for ($index = 0; $index < count($arrRequests) -1; $index++) {

	        foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
	        {
	            if ($i === 0)
	                $headers[$index]['http_code'] = self::getStatusCode($line);
	            else
	            {
	                list ($key, $value) = explode(': ', $line);
	                $headers[$index][$key] = $value;
	            }
	        }
	    }

	    return $headers;
	}

	private static function getStatusCode($codeString) {
		if (substr($codeString, 0, 4) == 'HTTP' && strpos($codeString, ' ') !== false) {
			$status_code = (int) substr($codeString, strpos($codeString, ' '), 4); // parse status code
		} else {
			$status_code = 500;	// Something went wrong
		}

		return $status_code;
	}
}
