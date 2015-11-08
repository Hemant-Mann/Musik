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

		$limit = RequestMethods::get("limit", 10);
		$page = RequestMethods::get("page", 1);
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
				"yid" => $track->yid,
				"lyric_id" => $l->id
			);
		}
		$all = ArrayMethods::toObject($all);

		$view->set("lyrics", $all);
		$view->set("count", $total);
		$view->set("limit", $limit);
		$view->set("page", (int) $page);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function edit($id) {
		$id = (int) $id;
		if (!$id) {
			self::redirect("/lyrics/all");
		}

		$this->seo(array("title" => "Lyrics | Edit", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$lyrics = \Lyric::first(array("id = ?" => $id));

		if (!$lyrics) {
			self::redirect("/lyrics/all");
		}
		$track = \SavedTrack::first(array("id = ?" => $lyrics->strack_id));

		if (RequestMethods::post("action") == "editLyrics") {
			$track->track = RequestMethods::post("track");
			$track->artist = RequestMethods::post("artist");
			$track->save();

			$lyrics->lyrics = stripslashes(RequestMethods::post("lyrics"));
			$lyrics->save();

			$view->set("success", "Lyrics Saved Successfully!!");
		}

		$data = array(
			"display" => $lyrics->lyrics,
			"artist" => $track->artist,
			"track" => $track->track
		);
		$data = ArrayMethods::toObject($data);
		$view->set("lyrics", $data);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function add() {
		$this->seo(array("title" => "Lyrics | Add New Lyrics", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		if (RequestMethods::post("action") == "addLyrics") {
			$session = Registry::get("session");
			$session->set('Home\findLyrics:$return', true);
			$yid = $this->findTrack();
			$session->erase('Home\findLyrics:$return');

			$strack = \SavedTrack::first(array("yid = ?" => $yid));
			if (!$strack) {
				$strack = new \SavedTrack(array(
					"track" => RequestMethods::post("track"),
					"artist" => RequestMethods::post("artist"),
					"yid" => RequestMethods::post("yid"),
					"mbid" => RequestMethods::post("mbid")
				));
				$strack->save();
			}

			$lyrics = \Lyric::first(array("strack_id = ?" => $strack->id));
			if ($lyrics) {
				$view->set("message", 'Lyrics already exists. Go to <a href="/lyrics/edit/'. $lyrics->id .'">Edit</a>');
			} else {
				$lyrics = new \Lyric(array(
					"strack_id" => $strack->id,
					"lyrics" => stripslashes(RequestMethods::post("lyrics"))
				));
				$lyrics->save();
				$view->set("message", 'Lyrics saved Successfully!! Go to <a href="/lyrics/all">All Lyrics</a>');
			}
		}
	}
}