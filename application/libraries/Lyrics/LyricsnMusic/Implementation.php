<?php
namespace Lyrics\LyricsnMusic;
use WebBot\lib\WebBot\Bot as Bot;

use Lyrics\Exceptions\LyricsnMusic\Request as Req;
use Lyrics\Exceptions\LyricsnMusic\Response as Response;

class Implementation {
	/**
	 * Name of the track for which lyrics is to be found
	 * @var string
	 */
	protected $_track;

	/**
	 * Name of the track artist
	 * @var string
	 */
	protected $_artist;

	/**
	 * Lyrics URL of the track
	 * @var string
	 */
	protected $_url;

	/**
	 * Stores the lyrics of the track
	 * @var string
	 */
	protected $_lyrics;

	/**
	 * Stores the xPath of the lyrics document
	 * @var XPath Object
	 */
	protected $_xPath;

	public function __construct($url, $track, $artist) {
		$this->_url = $url;
		$this->_track = $track;
		$this->_artist = $artist;

		$this->_parse();
	}

	protected function _getLyricsPage($url) {
		$bot = new Bot(array('lyrics_page' => $url));
		$bot->execute();
		$document = array_shift($bot->getDocuments());
		if (!$document) {
			throw new Req("Failed to get the lyrics page");
		}
		$this->_xPath = $document->returnXPathObject();
	}

	protected function _query($string) {
		$el = $this->_xPath->query($string)->item(0);

		if (!$el) {
			throw new Response('Could Not find the lyrics');
		}

		return $el->nodeValue;
	}

	/**
	 * Parses the page to make a new XPath object and queries the page to find the
	 * lyrics element and scrapes the lyrics
	 *
	 * @param string $url URL Of the lyrics page
	 */
	protected function _parse() {
		$this->_getLyricsPage($this->_url);

		$result = $this->_query('//*[@id="main"]/pre');

		$this->_lyrics = '<pre>' . $result . '</pre>';
	}

	public function getLyrics() {
		return $this->_lyrics;
	}

	public function getArtist() {
		return $this->_artist;
	}

	public function getTrack() {
		return $this->_track;
	}

	public function getUrl() {
		return $this->_url;
	}
}
