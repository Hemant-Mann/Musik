<?php

/**
 * The Home Controller
 *
 * @author Hemant Mann
 */
use Shared\Controller as Controller;
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;

// LastFm Library
use LastFm\Src\Track as Trck;
use LastFm\Src\Geo as Geo;
use LastFm\Src\Artist as Artst;
use LastFm\Src\Tag as Tag;

use Lyrics\LoloLyrics as LoloLyrics;
use Lyrics\Exceptions\Lolo\Response as Response;
use Lyrics\Exceptions\Lolo\Request as Req;

class Home extends Controller {

    public function index() {
        $view = $this->getActionView();
        $session = Registry::get("session");

        // @TODO find a better way to do this because this is preventing site
        // from loading on first time
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
        if (!$session->get('Home\Genres:$topTags')) {
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
            $session->set('Home\Genres:$topTags', $tags);
        }
        
        // Display songs for 'Genre' if given
        $tracks = array();
        if (!$session->get('Home\Genre:$set') || $session->get('Home\Genre:$set') != $name) {
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
            $session->set('Home\Genre:$set', $name);
            $session->set('Home\Genre:$topTracks', $tracks);
        }

        $view->set("genre",  ucfirst($session->get('Home\Genre:$set')));
        $view->set("tags", $session->get('Home\Genres:$topTags'));
        $view->set("tracks", $session->get('Home\Genre:$topTracks'));
        
    }

    public function events() {
        // code to list all the events
    }

    public function listen($artistName) {
        self::redirect("/404");
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
        $this->noview();
        $return = Registry::get("session")->get('Home\findLyrics:$return');
        if (RequestMethods::post("action") == "findTrack" || $return) {
            $artist = RequestMethods::post("artist");
            $track = RequestMethods::post("track");

            $videoId = null; $error = null;
            $q = $track. " ". $artist;

            $videoId = $this->searchYoutube($q, 1, true);
            if ($videoId != "Error") {
                if ($return) {
                    return $videoId;
                }
            }
            echo $videoId;

        } else {
            self::redirect("/404");
        }
    }

    public function findLyrics() {
        $this->noview();
        if (RequestMethods::post("action") == "findLyrics") {
            $artist = RequestMethods::post("artist");
            $track = RequestMethods::post("track");
            $mbid = RequestMethods::post("mbid");

            if ($mbid) {
                $where = array("mbid = ?" => $mbid, "live = ?" => true);
            } else {
                $where = array("artist = ?" => $artist, "track = ?" => $track, "live = ?" => true);
            }
            $strack = SavedTrack::first($where, array("id", "yid"));
            if (!$strack) {
                Registry::get("session")->set('Home\findLyrics:$return', true);
                $id = $this->findTrack();
                $strack = new SavedTrack(array(
                    "track" => $track,
                    "artist" => $artist,
                    "mbid" => $mbid,
                    "yid" => $id
                ));
                $strack->save();
            }

            $lyric = Lyric::first(array("strack_id = ?" => $strack->id, "live = ?" => true));
            Registry::get("session")->get('Home\findLyrics:$return', false);
            if ($lyric) {
                echo $lyric->lyrics;
                return;
            }
            try {
                $api = LoloLyrics::findLyrics($track, $artist);

                $result = $api->getLyrics();
                $lyrics = "<pre>". print_r($result, true). "</pre>";
                echo $lyrics;
                
                $lyric = new Lyric(array(
                    "lyrics" => $lyrics,
                    "strack_id" => $strack->id
                ));
                $lyric->save();    
            
            } catch (Req $e) {
                echo $e->getCustomMessage();
            } catch (Response $e) {
                echo $e->getCustomMessage();
            }
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
