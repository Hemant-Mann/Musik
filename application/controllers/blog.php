<?php

/**
 * Description of artists
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Blog extends Home {

    public function index() {
        $this->seo(array("title" => "Blog | Musik", "keywords" => "Music Blog", "description" => "Music Blog", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
    }

    public function all() {
        
    }

    public function create() {
        
    }

    public function post($title, $id) {
        
    }

}