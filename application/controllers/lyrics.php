<?php

/**
 * The Lyrics Controller
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;
use Framework\Registry as Registry;

class Lyrics extends Admin {

	/**
	 * @before _secure, changeLayout
	 */
	public function all() {
		$this->seo(array("title" => "Lyrics | All", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$limit = RequestMethods::get("perPage", 10);
		$page = RequestMethods::get("pageNo", 1);
		$orderBy = RequestMethods::get("orderBy", "created");

		$lyrics = \Lyric::all(array(), array("lyrics", "strack_id", "id"), $orderBy, "desc", $limit, $page);
		
		$total = \Lyric::count();
		$all = array();
		foreach ($lyrics as $l) {
			$track = \SavedTrack::first(array("id = ?" => $l->strack_id), array("track", "artist", "yid"));

			$all[] = array(
				"track" => $track->track,
				"artist" => $track->artist,
				"lyrics" => substr($l->lyrics, 0, 100). "</pre>",
				// "lyrics" => $l->lyrics,
				"yid" => $track->yid,
				"lyric_id" => $l->id
			);
		}
		$all = ArrayMethods::toObject($all);

		$view->set("lyrics", $all);
		$view->set("total", $total);
		$view->set("limit", $limit);
		$view->set("currentPage", (int) $page);
	}
}