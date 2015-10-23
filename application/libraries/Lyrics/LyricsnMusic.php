<?php
namespace Lyrics;
use WebBot\lib\WebBot\Bot as Bot;
use Lyrics\LyricsnMusic\Implementation as Implementation;

use Lyrics\Exceptions\LyricsnMusic\Response as Response;
use Lyrics\Exceptions\LyricsnMusic\Request as Req;
use Lyrics\Exceptions\LyricsnMusic\Argument as Argument;

class LyricsnMusic {
	/**
	 * Stores the API key for the API
	 * @var string
	 */
	protected static $_apiKey = 'API_KEY';

	/**
	 * The API Root for sending the request
	 * @var string
	 */
	protected static $apiRoot = 'http://api.lyricsnmusic.com/songs';

	public static function findLyrics($track, $artist) {
		if (empty($track) || empty($artist)) {
			throw new Argument('Both $track and $artist param are required');
		}
		$params = array(
			'api_key' => self::$_apiKey,
			'artist' => $artist,
			'track' => $track,
		);

		$result = self::_executeRequest(array('api_result' => self::_buildUrl($params)));
		return self::_parseData($result);
	}

	protected static function _executeRequest($urls) {
		$bot = new Bot($urls);
		$bot->execute();
		$document = array_shift($bot->getDocuments());
		if (!$document) {
			throw new Req("Failed to get the api results");
		}
		return $document->getHttpResponse()->getBody();
	}

	protected static function _buildUrl($params) {
		$queryString = http_build_query($params);

		return self::$apiRoot . '?' . $queryString;
	}

	protected static function _parseData($data) {
		$data = json_decode($data);
		$result = $data[0];

		if ($result->viewable) {
			return new Implementation($result->url, $result->title, $result->artist->name);	
		} else {
			throw new Response("Could Not find the lyrics");
		}
	}
}
