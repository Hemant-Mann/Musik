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

        if (!$session->get("topArtists")) {
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
            $session->set("topArtists", $artists);
        }

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
        $view->set("artists", $session->get("topArtists"));
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
        if (RequestMethods::post("action") == "search") {
         	$q = RequestMethods::post("q");

        }
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
        
        $view->set("results", $results);
    }

    public function findTrack() {
        $view = $this->noview();

        if (RequestMethods::post("action") == "findTrack") {
            $artist = RequestMethods::post("artist");
            $track = RequestMethods::post("track");

            $videoId = null; $error = null;
            $q = $track. " ". $artist;
            $youtube = Registry::get("youtube");
            try {
               $searchResponse = $youtube->search->listSearch('id', array(
                   'q' => $q,
                   'maxResults' => "1",
                   "type" => "video"
               ));
               // $view->set("response", $searchResponse);
               foreach ($searchResponse['items'] as $searchResult) {
                   $videoId = $searchResult['id']['videoId'];
               }
               
            } catch (Google_Service_Exception $e) {
                $error = 'An error occured';                 
            } catch (Google_Exception $e) {
                $error = 'An error occured';
            }

            if ($videoId) {
                echo "$videoId";
            } elseif ($error) {
                echo "Error";
            }
        } else {
            self::redirect("/404");
        }
    }

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

}
