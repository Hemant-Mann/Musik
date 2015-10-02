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
use LastFm\Src\Track as Track;

class Home extends Controller {

    public function index() {
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "search") {
            $q = RequestMethods::post("q");

            $tracks = @Track::search($q, null, 30)->getResults();    // Will return an array of objects
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
                    "image" => $t->getImage(2)
                );
            }
            $results = ArrayMethods::toObject($results);
            $view->set("results", $results);
        }
    }

    public function genres() {
        
    }

    public function events() {
        // code to list all the events
    }

    public function listen() {
        
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

}
