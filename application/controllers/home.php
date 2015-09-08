<?php

/**
 * The Default Example Controller Class
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Home extends Controller {

    public function index() {
        
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

         	$client = Registry::get("gClient");
         	$youtube = new Google_Service_YouTube($client);
         	
         	try {
         	  $searchResponse = $youtube->search->listSearch('id,snippet', array(
         	    'q' => $q,
         	    'maxResults' => "25",
         	    "type" => "video"
         	  ));

         	  // Add each result to the appropriate list, and then display the lists of
         	  // matching videos, channels, and playlists.
         	  foreach ($searchResponse['items'] as $searchResult) {
	 	          $thumbnail = $searchResult['snippet']['thumbnails']['medium']['url'];
	 	          $title = $searchResult['snippet']['title'];
	 	          $href = $searchResult['id']['videoId'];

	 	          $text .= "<li><a href=\"https://www.youtube.com/watch?v={$href}\" class=\"thumbnail\"><img src=\"$thumbnail\">$title</a></li><br />";
	 	      
         	  }
         	  $results .= "<h3 class=\"page-heading\">Videos</h3>
         	  <ul>$text</ul>";
         	} catch (Google_Service_Exception $e) {
         	  $results .= sprintf('<p>A service error occurred: <code>%s</code></p>',
         	    htmlspecialchars($e->getMessage()));
         	} catch (Google_Exception $e) {
         	  $results .= sprintf('<p>An client error occurred: <code>%s</code></p>',
         	    htmlspecialchars($e->getMessage()));
         	}
        }

        $view->set("results", $results);
    }
    
}
