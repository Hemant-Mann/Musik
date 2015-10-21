<?php
namespace Lyrics;
use WebBot\lib\WebBot\Bot as Bot;
use Lyrics\Exceptions\Lolo\Response as Response;
use Lyrics\Exceptions\Lolo\Request as Req;
use Lyrics\Exceptions\Lolo\Argument as Argument;

/**
 * Free Lyrics API with easy usage
 */
class LoloLyrics {
	/**
	 * Year in which track was released
	 * @var string
	 */
	protected $_year;

	/**
	 * Lyrics of the track sent by the server in response
	 * @var string
	 */
	protected $_response;

	/**
	 * Album of the track
	 * @var string
	 */
	protected $_album;

	/**
	 * API root
	 * @var string
	 */
	protected static $apiUri = 'http://api.lololyrics.com/0.5/';

	public function __construct($response, $album = "", $year = "") {
		$this->_response = $response;
		$this->_album = $album;
		$this->_year = $year;
	}

	public function getLyrics() {
		return $this->_response;
	}

	public function getYear() {
		return $this->_year;
	}
	
	public function getAlbum() {
		return $this->_album;
	}

	public static function findLyrics($track, $artist) {
		if (empty($track) || empty($artist)) {
			throw new Argument('Both $track and $artist param are required');
		}
		$params = array(
			'artist' => $artist,
			'track' => $track
		);

		$result = self::executeRequest(array('lyrics' => self::buildUrl('getLyric', $params)));
		return self::parseData($result);
	}

	protected static function executeRequest($urls) {
		$bot = new Bot($urls);
		$bot->execute();
		$document = array_shift($bot->getDocuments());
		if (!$document) {
			throw new Req("Failed to get the lyrics document");
		}
		return $document->getHttpResponse()->getBody();
	}

	protected static function buildUrl($method, $params) {
		$queryString = http_build_query($params);

		return self::$apiUri . $method . '?' . $queryString;
	}

	protected static function parseData($data) {
		$doc = new \SimpleXmlElement($data);
		
		if ((string) $doc->status == "OK") {
			return new LoloLyrics((string) $doc->response, (string) $doc->album, (string) $doc->year);	
		} else {
			throw new Response("Could Not find the lyrics");
		}
	}
}
