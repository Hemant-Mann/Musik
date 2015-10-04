<?php

/**
 * The Default Example Controller Class
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;
use LastFm\Src\Track as Trck;
use LastFm\Src\Geo as Geo;
use LastFm\Src\Artist as Artst;

class Home extends Controller {

    public function index() {
        $view = $this->getActionView();

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

        if (RequestMethods::post("action") == "search") {
            $q = RequestMethods::post("q");

            $tracks = @Trck::search($q, null, 30)->getResults();    // Will return an array of objects
            // echo "<pre>". print_r($tracks, true). "</pre>";
            
            $results = array();
            foreach ($tracks as $t) {
                $results[] = array(
                    "artist" => $t->getArtist(),
                    "album" => $t->getAlbum(),
                    "duration" => $t->getDuration(),
                    "wiki" => $t->getWiki(),
                    "mbid" => $t->getMbid(),
                    "url" => $t->getUrl(),
                    "image" => $t->getImage(4)
                );
            }
            $results = ArrayMethods::toObject($results);
            $view->set("results", $results);
        }

        $view->set("count", array(1,2,3,4,5));
        $view->set("artists", $artists);
    }

    public function genres() {
        
    }

    public function events() {
        // code to list all the events
    }

    public function listen($artistName) {
        $view = $this->getActionView();

        if (isset($artistName)) {

            $artist = Artst::getInfo($artistName);

            $art = array();
            $art["name"] = $artist->getName();
            $art["listeners"] = $artist->getPlayCount();
            $art["image"] = $artist->getImage(4);
            $art["mbid"] = $artist->getMbid();
            $artist = ArrayMethods::toObject($art);

            $topTracks = Artst::getTopTracks($artistName);

            $view->set("artist", $artist);
        } else {
            $topTracks = Geo::getTopTracks("India");
        }


        $tracks = array();
        foreach ($topTracks as $track) {
            $tracks[] = array(
                "duration" => $track->getDuration(),
                "album" => $track->getAlbum(),
                "name" => $track->getName(),
                "artist" => $track->getArtist()
            );

        }
        $tracks = ArrayMethods::toObject($tracks);

        $view->set("tracks", $tracks);
        
    }

    public function videos() {
    	$view = $this->getActionView();
    	$results = null; $text = '';

        if (RequestMethods::post("action") == "search") {
         	$q = RequestMethods::post("q");
            $youtube = Registry::get("youtube");
         	
         	try {
                $searchResponse = $youtube->search->listSearch('id,snippet', array(
                    'q' => $q,
                    'maxResults' => "15",
                    "type" => "video"
                ));

                // Add each result to the appropriate list, and then display the lists of
                // matching videos, channels, and playlists.
                $results = array();
                foreach ($searchResponse['items'] as $searchResult) {
                    $thumbnail = $searchResult['snippet']['thumbnails']['medium']['url'];
                    $title = $searchResult['snippet']['title'];
                    $href = $searchResult['id']['videoId'];

                    $results[] = array(
                        "img" => $thumbnail,
                        "title" => $title,
                        "videoId" => $href
                    );
                }
                $results = ArrayMethods::toObject($results);
    
                } catch (Google_Service_Exception $e) {
                    $error = 'A Service error occured';
                    $results = null;
                } catch (Google_Exception $e) {
                    $error = 'A Client error occured';
                    $results = null;
                }
        }

        $view->set("results", $results);
    }

    public function artists() {
        $view = $this->getActionView();


        $title = "Top Artists";
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

        $view->set("count", array(1,2,3,4,5));
        $view->set("artists", $artists);


        $view->set("title", $title);
    }

}
