<?php

/**
 * Description of genres
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Genres extends Admin {

	/**
	 * List all the genres
	 * @before _secure, changeLayout
	 */
	public function all() {
		$this->seo(array("title" => "Musik | List Genres", "keywords" => "All Music Genres", "description" => "admin", "view" => $this->getLayoutView()));

		$view = $this->getActionView();
		$genres = Genre::all(array("live = ?" => true));
		$view->set("genres", $genres);
	}

	/**
	 * Edit an Genre if exists else add a new genre
	 * @before _secure, changeLayout
	 */
	public function edit($id = NULL) {
		if (isset($id)) {
			$genre = Genre::first(array("id = ?" => $id));
			if (!$genre) {
				self::redirect("/genres/all");
			}

			$edit = true;	// Editing an Genre
		} else {
			$genre = new Genre(array());
			$edit = false;	// New Genre
		}

		$title = ($edit) ? "Edit Genre" : "Add Genre";
		$this->seo(array("title" => "Musik | ".$title, "keywords" => "Add an genre, edit an genre", "description" => "admin", "view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$action = RequestMethods::post("action");
		if ($action == "editGenre" || $action == "addGenre") {
			$genre->title = RequestMethods::post("title");
			$genre->live = true;
			$genre->deleted = false;

			$genre->save();
			$view->set("success", "Genre saved <strong>successfully</strong>");
		}

		$view->set("edit", $edit);
		$view->set("genre", $genre);
	}
}