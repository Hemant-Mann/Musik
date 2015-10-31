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

class Artists extends Home {

	public function view($name, $id = NULL) {
    	$view = $this->getActionView();
        $session = Registry::get("session");
        if (empty($name)) {
            self::redirect("/404");
        }

        $seo = $this->seoOptimize();
        $this->seo(array(
            "title" => "Musik | View Artist - ". $name,
            "keywords" => $seo["keywords"] . "Listen to $name",
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));

        if ($session->get('Artists\View:name') != $name) {
            try {
                $art = Art::getInfo($name);

                // artist details
                $artist = array();
                $artist["name"] = $art->getName();
                $artist["mbid"] = $art->getMbid();
                $artist["bio"] = $art->getBiography();
                $artist["thumbnail"] = $art->getImage(1);
                $artist["image"] = $art->getImage(4);
                $artist["playCount"] = $art->getPlayCount();
                $artist["listeners"] = $art->getListeners();

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
                $tags = Art::getTopTags($art->getName());
                $artist["tags"] = array();
                foreach ($tags as $t) {
                    $artist["tags"][] = array(
                        "name" => $t->getName()
                    );
                }
                $artist = ArrayMethods::toObject($artist);
                
                // Top tracks of an artist
                $topTracks = Art::getTopTracks($art->getName());
                $tracks = array();
                foreach ($topTracks as $t) {
                    $tracks[] = array(
                        "name" => $t->getName(),
                        "played" => $t->getPlayCount(),
                        "mbid" => $t->getMbid()
                    );
                }
                $tracks = ArrayMethods::toObject($tracks);

                $session->set('Artists\View:name', $name);
                $session->set('Artists\View:$artist', $artist);
                $session->set('Artists\View:$similar', $similar);
                $session->set('Artists\View:$tracks', $tracks);

            } catch (\Exception $e) {
                $session->set('Artists\View:$notFound', true);
                self::redirect('/artists/view/'. $session->get('Artists\View:name'));
            }
                
        }

        if ($session->get('Tracks\View:$notFound')) {
            $view->set("error", "Could not find the track details");
            $session->erase('Tracks\View:$notFound');
        }

        if ($session->get('Artists\View:$notFound')) {
            $view->set("error", "Could not find the artist details");
            $session->erase('Artists\View:$notFound');
        }
    	
        $view->set("similar", $session->get('Artists\View:$similar'));
        $view->set("tracks", $session->get('Artists\View:$tracks'));
        $view->set("artist", $session->get('Artists\View:$artist'));

    }

	public function top($page = 1) {
        $view = $this->getActionView();
        if (is_numeric($page) === FALSE) { self::redirect("/artists/top/"); }
        
        $page = (int) $page; $pageMax = 50;
        if ($page > $pageMax) {
            $page = $pageMax;
        } elseif ($page === 0) {
            $page = 1;
        }
		$session = Registry::get("session");
        
        if (!$session->get("country")) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $country = $this->getCountry($ip);
            $session->set("country", $country);
        }

        $seo = $this->seoOptimize();
        $this->seo(array(
            "title" => "Musik | Top Artists - ". $session->get("country"),
            "keywords" => $seo["keywords"] . "Top Artists of " . $session->get("country"),
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));

        $title = "Top Artists - " . $session->get("country");

        if (!$session->get('Artists\Top:$geo') || $session->get('Artists\Top:page') != $page) {
        	$topArtists = Geo::getTopArtists($session->get("country"), $page);

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
            $session->set('Artists\Top:page', $page);
        	$session->set('Artists\Top:$geo', $artists);
        }

        $view->set("count", array(1,2,3,4,5));
        $view->set("pagination", $this->setPagination("/artists/top/", $page, 1, $pageMax));
        $view->set("artists", $session->get('Artists\Top:$geo'));
        $view->set("title", $title);
	}

}