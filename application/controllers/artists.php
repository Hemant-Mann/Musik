<?php

/**
 * Description of artists
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use LastFm\Src\Geo as Geo;
use LastFm\Src\Artist as Art;
use Framework\ArrayMethods as ArrayMethods;

class Artists extends Admin {

	public function view($name, $id = NULL) {
    	$view = $this->getActionView();

        if (empty($name)) {
            self::redirect("/404");
        }
    	$art = Art::getInfo($name);

        // artist details
        $artist = array();
        $artist["name"] = $art->getName();
        $artist["mbid"] = $art->getMbid();
        $artist["bio"] = $art->getBiography();
        $artist["thumbnail"] = $art->getImage(1);
        $artist["image"] = $art->getImage(4);

        // Get similar artists
        $similarArtists = $art->getSimilarArtists();
        $similar = array();
        foreach ($similarArtists as $a) {
            $similar[] = array(
                "name" => $a->getName(),
                "thumbnail" => $a->getImage(2)
            );
        }
        $similar = ArrayMethods::toObject($similar);

        // Tags of artist
        $tags = Art::getTopTags(null, $art->getMbid());
        foreach ($tags as $t) {
            $artist["tags"][] = array(
                "name" => $t->getName()
            );
        }
        $artist = ArrayMethods::toObject($artist);
        
        // Top tracks of an artist
        $topTracks = Art::getTopTracks(null, $art->getMbid());
        $tracks = array();
        foreach ($topTracks as $t) {
            $tracks[] = array(
                "name" => $t->getName(),
                "played" => $t->getPlayCount(),
                "mbid" => $t->getMbid()
            );
        }
        $tracks = ArrayMethods::toObject($tracks);

        $view->set("similar", $similar);
        $view->set("tracks", $tracks);
        $view->set("artist", $artist);
	}

	public function top() {
		$view = $this->getActionView();
		$session = Registry::get("session");

        $title = "Top Artists";
        
        if (!$session->get("topArtistsCountry")) {
        	// Show top Artist by country
        	$topArtists = Geo::getTopArtists("India");

        	$artists = array();
        	$i = 1;
        	foreach ($topArtists as $art) {
        	    $artists[] = array(
        	        "mbid" => $art->getMbid(),
        	        "name" => $art->getName(),
        	        "image" => $art->getImage(4)
        	    );
        	    ++$i;

        	    if ($i > 30) break;
        	}
        	$artists = ArrayMethods::toObject($artists);
        	$session->set("topArtistsCountry", $artists);
        }
            

        $view->set("count", array(1,2,3,4,5));
        $view->set("artists", $artists);

        $view->set("title", $title);
	}

}