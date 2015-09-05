<?php

/**
 * Description of albums
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Albums extends Admin {

	/**
	 * List all the albums
	 * @before _secure, changeLayout
	 */
	public function all() {
		$this->seo(array("title" => "Musik | List Albums", "keywords" => "Find All Albums", "description" => "admin", "view" => $this->getLayoutView()));

		$view = $this->getActionView();
		$albums = Album::all(array("live = ?" => true));
		$view->set("albums", $albums);
	}

	/**
	 * Find all the tracks for a given album id
	 * @before _secure, changeLayout
	 */
	public function tracks($id = NULL) {
		$this->checkValidRequest($id, "/albums/all");

		$this->seo(array("title" => "Musik | Album Tracks", "keywords" => "All tracks of an album", "description" => "admin", "view" => $this->getLayoutView()));

		$view = $this->getActionView();
		$album = Album::first(array("id = ?" => $id));
		$tracks = Track::all(array("album_id = ?" => $id));
		$view->set("tracks", $tracks);
		$view->set("album", $album);
	}

	/**
	 * Edit an Album if exists else add a new album
	 * @before _secure, changeLayout
	 */
	public function edit($title= NULL, $id = NULL) {
		if (isset($id)) {
			$album = Album::first(array("id = ?" => $id));
			if (!$album) {
				self::redirect("/albums/all");
			}

			$edit = true;	// Editing an Album
		} else {
			$album = new Album(array());
			$edit = false;	// New Album
		}
		$title = ($edit) ? "Edit Album" : "Add Album";
		$this->seo(array("title" => "Musik | ".$title, "keywords" => "Add an album, edit an album", "description" => "admin", "view" => $this->getLayoutView()));

		$view = $this->getActionView();

		$action = RequestMethods::post("action");
		if ($action == "editAlbum" || $action == "addAlbum") {
			$album->title = RequestMethods::post("title");
			$album->year = RequestMethods::post("year");
			$album->cover = RequestMethods::post("cover");
			$album->genre_id = RequestMethods::post("genre");
			$album->live = true;
			$album->deleted = false;

			$album->save();
			$view->set("success", "Album saved <strong>successfully</strong>");
		}

		$view->set("edit", $edit);
		$view->set("album", $album);
	}

	public function view($view, $id = NULL) {
		if (isset($id)) {
			$album = Album::first(array("id = ?" => $id));
		}

		if (!$album) {
			self::redirect("/albums/all");
		}

		$this->seo(array("title" => "Musik | View Album", "keywords" => "View an album", "description" => "admin", "view" => $this->getLayoutView()));

		$view = $this->getActionView();
		$view->set("album", $album);
	}

	/**
	 * Find all the genres for a given id
	 */
	public function genres($title = NULL) {
		$this->checkValidRequest($title, "/albums/all");

		// find the albums
		$genre = Genre::first(array("title = ?" => $title));
		$albums = Album::all(array("genre_id = ?" => $genre->id, "live = ?" => true));
		if (empty($albums)) {
			self::redirect("/albums/all");
		}

		$this->seo(array("title" => "Musik | Album Tracks", "keywords" => "Find albums by genre", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$view->set("albums", $albums);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function artists($albumId = NULL) {
		$this->checkValidRequest($albumId, "/albums/all");

		$artistalbum = ArtistAlbum::all(array("album_id = ?" => $albumId));
		$album = Album::first(array("id = ?" => $albumId), array("title", "id"));
		
		$this->seo(array("title" => "Musik | Album Artists", "keywords" => "Artists of an album", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		// check if any notification stored in the session
		$session = Registry::get("session");
		$notification = $session->get("success");
		if ($notification) {
			$view->set("success", $notification);
			$session->erase("success");
		}

		$view->set("album", $album);
		$view->set("artists", $artistalbum);

	}

	/**
	 * @before _secure, changeLayout
	 */	
	public function toggleArtist($albumId = NULL, $artistId = NULL) {
		$this->checkValidRequest($albumId, "/albums/all");

		$artist = ArtistAlbum::first(array("album_id = ?" => $albumId, "artist_id = ?" => $artistId));

		if (!$artist) {
			self::redirect("/albums/all");
		}
		
		$live = $artist->live;
		if ($live) {
			$artist->live = false;
			$message = "Artist Removed Successfully";
		} else {
			$artist->live = true;
			$message = "Artist Added Successfully";
		}

		$artist->save();
		$session = Registry::get("session");
		$session->set("success", $message);
		self::redirect("/albums/artists/$albumId");
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function addArtist($albumId = NULL) {
		$this->checkValidRequest($albumId, "/albums/all");

		$albumArtist = new ArtistAlbum(array(
			"album_id" => $albumId
		));
		$artists = Artist::all(array(), array("name")); 
		$album = Album::first(array("id = ?" => $albumId));

		$this->seo(array("title" => "Musik | Album Add Artists", "keywords" => "Add Artists of an album", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		if (RequestMethods::post("action") == "addArtist") {
			$name = RequestMethods::post("artist");
			$artist = Artist::first(array("name = ?" => $name));

			if (!$artist) {	// new artist so save in db
				$artist = new Artist(array(
					"name" => $name,
					"live" => true,
					"deleted" => false
				));
				$artist->save();
			}

			$albumArtist->artist_id = $artist->id;
			$albumArtist->save();

			$view->set("success", "Artist Added Successfully");
		}

		$artists = empty($artists) ? array() : $artists;
		$view->set("artists", $artists);
		$view->set("album", $album);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function track($albumId = NULL, $id = NULL) {
		$this->checkValidRequest($albumId, "/albums/all");

		if ($id) {
			$track = Track::first(array("id = ?" => $id));
			if ($track) {
				$edit = true;
				$song = Song::first(array("id = ?" => $track->song_id));
			}
		} else {
			$edit = false;
			$track = new Track(array());
			$song = new Song(array());
		}
		$album = Album::first(array("id = ?" => $albumId));
		$artists = Artist::all(array(), array("name"));

		$title = ($edit) ? "Edit Track" : "Add Track";
		$this->seo(array("title" => "Musik | Album ".$title, "keywords" => "Add tracks of an album, edit tracks of an album", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		if (RequestMethods::post("action") == "saveTrack") {
			if (!$edit) {	// Adding a new song
				// code to upload mp3 file to the server
				$file = new File(array(
					"name" => "",
					"mime" => "audio/mpeg",
					"size" => "",
					"user" => $this->user->id,
					"live" => true,
					"deleted" => false
				));
				$file->save();
				$song->file_id = $file->id;
			}

			// save song
			$song->title = RequestMethods::post("title");
			$song->playtime = RequestMethods::post("playtime");
			$song->bitrate = RequestMethods::post("bitrate");
			$song->save();

			// save track
			$track->trackno = RequestMethods::post("trackno");
			$track->song_id = $song->id;
			$track->album_id = $albumId;
			$track->save();

			// save song's artist
			$name = RequestMethods::post("artist");
			$artist = Artist::first(array("name = ?" => $name));
			if (!$artist) {	// new artist so save in db
				$artist = new Artist(array(
					"name" => $name,
					"live" => true,
					"deleted" => false
				));
				$artist->save();
			}
			$songArtist = new SongArtist(array(
				"song_id" => $song->id,
				"artist_id" => $artist->id,
				"relation" => RequestMethods::post("relation"),
				"live" => true,
				"deleted" => false
			));
			$songArtist->save();

			$view->set("success", true);
		}

		$view->set("artists", $artists);
		$view->set("album", $album);
		$view->set("edit", $edit);
		$view->set("track", $track);
		$view->set("song", $song);
	}
}