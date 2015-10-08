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
use LastFm\Src\Tag as Tag;

class Home extends Controller {

    public function index() {
        $view = $this->getActionView();
        $session = Registry::get("session");

        if (!$session->get("country")) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $country = $this->getCountry($ip);
            $session->set("country", $country);
        }

        if (!$session->get('Home\Index:$topArtists')) {
            // Show top Artist by country
            $topArtists = Geo::getTopArtists($session->get("country"));
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
            $session->set('Home\Index:$topArtists', $artists);
        }

        $view->set("count", array(1,2,3,4,5));
        $view->set("artists", $session->get('Home\Index:$topArtists'));
    }

    public function genres($name = null) {
        $view = $this->getActionView();
        $session = Registry::get("session");

        if (!$name) {
            $name = "acoustic";
        }

        // Get top Tags for displaying - currently not working (Last Fm Fault)        
        if (!$session->get("topTags")) {
            $topTags = Genre::all(array(), array("title"));
            // $topTags = Tag::getTopTags();
            $tags = array();
            foreach ($topTags as $t) {
                $tags[] = array(
                    // "name" => $t->getName()
                    "name" => $t->title
                );
            }
            $tags = ArrayMethods::toObject($tags);
            $session->set("topTags", $tags);
        }
        
        // Display songs for 'Genre' if given
        $tracks = array();
        if (!$session->get("tagGetTracks") || $session->get("tagGetTracks") != $name) {
            $topTracks = Tag::getTopTracks($name);

            foreach ($topTracks as $t) {
                $tracks[] = array(
                    "name" => $t->getName(),
                    "mbid" => $t->getMbid(),
                    "artist" => $t->getArtist()->getName(),
                    "artistId" => $t->getArtist()->getMbid(),
                    "image" => $t->getImage(2)
                );
            }
            $tracks = ArrayMethods::toObject($tracks);
            $session->set("tagGetTracks", $name);
            $session->set("tagTopTracks", $tracks);
        }

        $view->set("genre", ucfirst($name));
        $view->set("tags", $session->get("topTags"));
        $view->set("tracks", $session->get("tagTopTracks"));
        
    }

    public function events() {
        // code to list all the events
    }

    public function listen($artistName) {
        $view = $this->getActionView();

        // find artist by name
        if (isset($artistName)) {
            $artist = Artst::getInfo($artistName);

            $art = array();
            $art["name"] = $artist->getName();
            $art["listeners"] = $artist->getPlayCount();
            $art["image"] = $artist->getImage(4);
            $art["mbid"] = $artist->getMbid();

            $similarArtists = $artist->getSimilarArtists();
            $artist = ArrayMethods::toObject($art);

            $topTracks = Artst::getTopTracks($artistName);

            $view->set("artist", $artist);
        } else {
            self::redirect("/404");
        }

        // Get top tracks
        $tracks = array();
        foreach ($topTracks as $track) {
            $tracks[] = array(
                "mbid" => $track->getMbid(),
                "name" => $track->getName(),
                "artist" => $track->getArtist()->getName()
            );

        }

        // Get similar artists
        $similar = array();
        foreach ($similarArtists as $art) {
            $similar[] = array(
                "name" => $art->getName(),
                "thumbnail" => $art->getImage(0)
            );
        }
        $similar = ArrayMethods::toObject($similar);
        $tracks = ArrayMethods::toObject($tracks);
        
        $view->set("tracks", $tracks);
        $view->set("similar", $similar);
    }

    public function videos() {
    	$view = $this->getActionView();
    	$results = null; $text = '';

        $q = 'latest songs';
        $results = $this->searchYoutube($q);

        // @todo add error checking in videos page
        if (!is_object($results) && $results == "Error") {
            $view->set("error", $results);
        } else {
            $view->set("results", $results);    
        }
    }

    /**
     * Finds the youtube video id of a given song
     */
    public function findTrack() {
        $view = $this->noview();

        if (RequestMethods::post("action") == "findTrack") {
            $artist = RequestMethods::post("artist");
            $track = RequestMethods::post("track");

            $videoId = null; $error = null;
            $q = $track. " ". $artist;

            $videoId = $this->searchYoutube($q, 1, true);
            echo $videoId;

        } else {
            self::redirect("/404");
        }
    }

    /**
     * Searches for music from the supplied query on last.fm | Youtube
     */
    public function searchMusic() {
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "searchMusic") {
            $type = RequestMethods::post("type");
            $q = RequestMethods::post("q");

            if ($type == 'song') {
                $results = $this->searchLastFm($q);
            } elseif ($type == 'video') {
                $results = $this->searchYoutube($q);
            }

            if (!is_object($results) && $results == "Error") {
            	$view->set("error", "Error");
            } else {
            	$view->set("type", $type);
            	$view->set("results", $results);
            }
        } else {
            self::redirect("/404");
        }
    }

    /**
     * Searches for country name based on client's IP Address
     */
    protected function getCountry($ip) {
        $ip = ($ip == '127.0.0.1') ? '203.122.5.25' : $ip;

        // $ip = '203.122.5.25';
        $this->noview();

        $url = 'http://www.geoplugin.net/json.gp?ip='.$ip;
        $ch = curl_init(); 

        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36');
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,15);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);                
        curl_setopt($ch, CURLOPT_URL, $url); 
        
        $data = curl_exec($ch);
        $data = json_decode($data);
        curl_close($ch);

        return $data->geoplugin_countryName;
    }

    /**
     * Searches last.fm for the given song
     */
    protected function searchLastFm($q) {
    	try {
    		$tracks = @Trck::search($q, null, 30)->getResults();    // Will return an array of objects
    		// echo "<pre>". print_r($tracks, true). "</pre>";
    		
    		$results = array();
    		foreach ($tracks as $t) {
    		    $results[] = array(
    		    	"name" => $t->getName(),
    		        "artist" => $t->getArtist(),
    		        "album" => $t->getAlbum(),
    		        "wiki" => $t->getWiki(),
    		        "mbid" => $t->getMbid(),
    		        "image" => $t->getImage(4)
    		    );
    		}
    		$results = ArrayMethods::toObject($results);
    		
    		return $results;	
    	} catch (\Exception $e) {
    		return "Error";
    	}
        
    }

    /**
     * Searches youtube for a given query
     * @return object|string
     */
    protected function searchYoutube($q, $max = 15, $returnId = false) {
        $youtube = Registry::get("youtube");
        
        try {
            $searchResponse = $youtube->search->listSearch('id,snippet', array(
                'q' => $q,
                'maxResults' => $max,
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
            return ($returnId) ? $href : $results;

        } catch (Google_Service_Exception $e) {
            return "Error";
        } catch (Google_Exception $e) {
            return "Error";
        }
    }
}
