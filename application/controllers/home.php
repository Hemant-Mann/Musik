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

use WebBot\lib\WebBot\Bot as Bot;

use YTDownloader\Download as YTDownload;
use YTDownloader\Exceptions\YoutubeDL as YoutubeDL;
use YTDownloader\Exceptions\FFmpeg as FFmpeg;

class Home extends Controller {

    public function index($page = 1) {
        $seo = $this->seoOptimize();
        $this->seo(array(
            "title" => "Musik | Web Application - Discover",
            "keywords" => $seo["keywords"],
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
        if (is_numeric($page) === FALSE) { self::redirect("/"); }
        
        $page = (int) $page; $pageMax = 50;
        if ($page > $pageMax) {
            $page = $pageMax;
        }  elseif ($page === 0) {
            $page = 1;
        }
        $session = Registry::get("session");

        if (!$session->get("country")) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $country = $this->getCountry($ip);
            $session->set("country", $country);
        }

        if (!$session->get('Home\Index:$topArtists') || $session->get('Home\Index:page') != $page) {
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

            $session->set('Home\Index:page', $page);
            $session->set('Home\Index:$topArtists', $artists);
        }

        $view->set("count", array(1,2,3,4,5));
        $view->set("pagination", $this->setPagination("/index/", $page, 1, $pageMax));
        $view->set("artists", $session->get('Home\Index:$topArtists'));
    }

    public function genres($name = null, $page = 1) {
        $seo = $this->seoOptimize();
        $view = $this->getActionView();
        $view = $this->getActionView();
        if (is_numeric($page) === FALSE) { self::redirect("/genres/all"); }
        
        $page = (int) $page; $pageMax = 5;
        if ($page > $pageMax) {
            $page = $pageMax;
        }  elseif ($page === 0) {
            $page = 1;
        }
        $session = Registry::get("session");

        if (!$name) {
            $name = "acoustic";
        }
        $this->seo(array(
            "title" => "Musik | Genres - ". $name,
            "keywords" => $seo["keywords"] . "{$name} music",
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));

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
        if ($session->get('Home\Genre:$set') != $name || $session->get('Home\Genre:page') != $page) {
            $topTracks = Tag::getTopTracks($name, $page);

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
            $session->set('Home\Genre:page', $page);
            $session->set('Home\Genre:$set', $name);
            $session->set('Home\Genre:$topTracks', $tracks);
        }

        $view->set("pagination", $this->setPagination("/genres/{$name}/", $page));
        $view->set("genre",  ucfirst($session->get('Home\Genre:$set')));
        $view->set("tags", $session->get('Home\Genres:$topTags'));
        $view->set("tracks", $session->get('Home\Genre:$topTracks'));
        
    }

    public function listen($artistName) {
        self::redirect("/404");
    }

    public function videos($page) {
        $seo = $this->seoOptimize();
    	$this->seo(array(
            "title" => "Musik | Videos - Latest Songs",
            "keywords" => $seo["keywords"],
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
        if (is_numeric($page) === FALSE) { self::redirect("/videos/1"); }
        
        $page = (int) $page; $pageMax = 6;
        if ($page > $pageMax) {
            $page = $pageMax;
        } elseif ($page === 0) {
            $page = 1;
        }

        $session = Registry::get("session");
        $key = 'Home\videos:$results:'.$page;
        $q = 'latest songs';
        
        if (!$session->get('Home\videos:$once')) {
            $session->set('Home\videos:$once', 1);

            $options = array('method' => 'Home\videos', 'current' => 1, 'token' => '');
            $results = $this->searchYoutube($q, 15, false, $options);
            $session->set('Home\videos:$results:1', $results);
            self::redirect("/videos/1");
        }

        if ($page != 1 && !$session->get($key)) {
            $currentPage = $session->get('Home\videos:$currentPage');

            if ($page == $currentPage + 1) { // next page
                $pageToken = $session->get('Home\videos:$nextPageToken');
            } elseif ($page === $currentPage - 1) { // prev page
                $pageToken = $session->get('Home\videos:$prevPageToken');
            } else {
                self::redirect("/videos/{$currentPage}");
            }

            $options = array('method' => 'Home\videos', 'current' => $page, 'pageToken' => $pageToken);
        }

        if (!$session->get($key)) {
            $results = $this->searchYoutube($q, 15, false, $options);
            $session->set($key, $results);
        }

        $view->set("results", $session->get($key));
        $view->set("pagination", $this->setPagination("/videos/", $page, 1, $pageMax));
    }

    /**
     * Finds the youtube video id of a given song
     */
    public function findTrack() {
        $this->noview();
        $return = Registry::get("session")->get('Home\findLyrics:$return');
        if ((RequestMethods::post("action") == "findTrack" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) || $return) {
            $artist = RequestMethods::post("artist");
            $track = RequestMethods::post("track");
            $videoType = RequestMethods::post("videoType", "Song");

            $videoId = null; $error = null;
            $q = $artist . " - " . $track . $videoType;

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
        if (RequestMethods::post("action") == "findLyrics" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
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

                if (RequestMethods::post("yid")) {
                    $id = RequestMethods::post("yid");    
                } else {
                    $id = $this->findTrack();    
                }
                $strack = new SavedTrack(array(
                    "track" => $track,
                    "artist" => $artist,
                    "mbid" => $mbid,
                    "yid" => $id
                ));
                $strack->save();
            }

            $lyric = Lyric::first(array("strack_id = ?" => $strack->id, "live = ?" => true));
            Registry::get("session")->erase('Home\findLyrics:$return');
            if ($lyric) {
                echo $lyric->lyrics;
                return;
            }
            
            $shared = new Shared\Lyrics(array('library' => 'LyricsnMusic', 'track' => $track, 'artist' => $artist));
            $api = $shared->findLyrics();
            
            if (is_object($api)) {
                $lyric = new Lyric(array(
                    "lyrics" => $api->getLyrics(),
                    "strack_id" => $strack->id
                ));
                $lyric->save();

                echo $api->getLyrics();
            } else {
                echo "Could not find the lyrics";
            }
        } else {
            self::redirect("/404");
        }
    }

    /**
     * Searches for music from the supplied query on last.fm | Youtube
     */
    public function searchMusic($page = 1) {
        $seo = $this->seoOptimize();
        $this->seo(array(
            "title" => "Musik | Videos - Search Music",
            "keywords" => $seo["keywords"],
            "description" => $seo["description"],
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
        if (is_numeric($page) === FALSE) { self::redirect("/"); }

        $page = (int) $page; $pageMax = 7;
        if ($page > $pageMax) {
            $page = $pageMax;
        } elseif ($page === 0) {
            $page = 1;
        }
        $session = Registry::get("session");
        $stored = $session->get('Home\searchMusic:$vars');

        if (RequestMethods::post("action") == "searchMusic") {
            $type = RequestMethods::post("type");
            $q = RequestMethods::post("q");

            if ($stored['q'] !== $q || $stored['type'] !== $type) {
                $this->setResults($type, $q, $page);
            }
        } elseif (!$stored || !$stored['results']) {
            self::redirect("/");
        }

        if ($stored['page'] != $page && $stored['type'] == 'song') {
            $this->setResults($stored['type'], $stored['q'], $page);
        }

        $stored = $session->get('Home\searchMusic:$vars');
        if ($stored['error']) {
            $view->set('error', $stored['error']);
        } else {
            $view->set('type', $stored['type']);
            $view->set('results', $stored['results']);
        }

        $view->set('pagination', $this->setPagination('/home/searchMusic/', $page, 1, $pageMax));
    }

    protected function setResults($type, $q, $page = 1, $limit = 50) {
        $session = Registry::get("session");
 
        switch ($type) {
            case 'song':
                $results = $this->searchLastFm($q, $page);
                break;
            
            case 'video':
                $results = $this->searchYoutube($q, $limit);
                break;
        }

        if ($results == "Error") {
            $session->set('Home\searchMusic:$vars', array('error' => 'Error'));
        } else {
            $session->set('Home\searchMusic:$vars', array('q' => $q, 'type' => $type, 'results' => $results, 'page' => $page));
        }
    }

    public function download($videoId, $name = 'track') {
        if (!$videoId) {
            self::redirect("/");
        }
        $this->noview();
        $url = 'https://www.youtube.com/watch?v=' . $videoId;
        $download = new YTDownload($url);
        
        $track = \SavedTrack::first(array("yid = ?" => $videoId), array("id"));
        $d = \Download::first(array("strack_id = ?" => $track->id));
        $sendFile = false;

        if (RequestMethods::post("action") == "downloadMusic") {
            try {
                $download->convert();
                $file = $download->getFile();

                if (!$track) {
                    $track = new S\avedTrack(array(
                        "track" => RequestMethods::post("track"),
                        "artist" => RequestMethods::post("artist"),
                        "mbid" => RequestMethods::post("mbid"),
                        "yid" => $videoId
                    ));
                    $track->save();
                }
                if (!$d) {
                   $d = new \Download(array(
                        "strack_id" => $track->id,
                        "count" => 0,
                        "modified" => date('Y-m-d H:i:s')
                    ));
                   $d->save();
                }
                echo "Success";
            } catch (YoutubeDL $e) {
                echo $e->getCustomMsg();
            } catch (FFmpeg $e) {
                echo $e->getCustomMsg();
            } catch (\Exception $e) {
                echo "Failure";
            }
            return;
        } else {
            $file = $download->getDownloadPath() . $videoId . ".mp3";
            $sendFile = true;
        }

        if ($track && $d && $sendFile) {
            $d->count++;
            $d->save();
            header('Content-type: audio/mpeg');
            header('Content-length: ' . filesize($file));
            header('Content-Disposition: attachment; filename="'.$name.'.mp3"');
            header("Content-Transfer-Encoding: binary"); 
            header("Content-Type: audio/mpeg, audio/x-mpeg, audio/x-mpeg-3, audio/mpeg3");

            readfile($file);
        }
    }

    /**
     * Searches for country name based on client's IP Address
     */
    protected function getCountry($ip) {
        $ip = ($ip == '127.0.0.1') ? '203.122.5.25' : $ip;

        $url = 'http://www.geoplugin.net/json.gp?ip='.$ip;
        $bot = new Bot(array('country' => $url));
        $bot->execute();
        $document = array_shift($bot->getDocuments());
        $data = json_decode($document->getHttpResponse()->getBody());

        return $data->geoplugin_countryName;
    }

    /**
     * Searches last.fm for the given song
     */
    protected function searchLastFm($q, $page = 1, $limit = 30) {
    	try {
    		$tracks = @Trck::search($q, null, $limit, $page)->getResults();    // Will return an array of objects
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
    protected function searchYoutube($q, $max = 15, $returnId = false, $options = array()) {
        $youtube = Registry::get("youtube");
        $session = Registry::get("session");

        if (!empty($options)) {
            $token = $options["pageToken"];
            $method = $options["method"];
            $session->set($method. ':$currentPage', $options["current"]);
        } else {
            $token = ''; $method = '';
        }

        try {
            $searchResponse = $youtube->search->listSearch('id,snippet', array(
                'q' => $q,
                'maxResults' => $max,
                "type" => "video",
                "pageToken" => $token
            ));

            if ($method) {
                $session->set($method.':$nextPageToken', $searchResponse['nextPageToken']);
                $session->set($method.':$prevPageToken', (isset($searchResponse['prevPageToken']) ? $searchResponse['prevPageToken'] : false));    
            }
            
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
