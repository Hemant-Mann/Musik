<?php

/**
 * Description of artists
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Blog extends Admin {

    public function index() {
        $this->seo(array("title" => "Blog | Musik", "keywords" => "Music Blog", "description" => "Music Blog", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $limit = RequestMethods::get("limit", 10);
        $page = RequestMethods::get("page", 1);

        $posts = \Post::all(array("live = ?" => true), array("title", "content", "id", "created"), "created", "desc", $limit, $page);
        $downloads = \Download::all(array("live = ?" => true), array("count", "strack_id", "id"), "count", "desc", 10, 1);
        
        $view->set('posts', $posts);
        $view->set("results", $downloads);
    }

    /**
     * @before _secure, changeLayout
     */
    public function all() {
        $this->seo(array("title" => "All Blog Post", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $limit = RequestMethods::get("limit", 10);
        $page = RequestMethods::get("page", 1);

        $posts = Post::all(array(), array("title", "created", "live", "id"), "created", "desc", $limit, $page);
        $count = Post::count();

        $view->set("posts", $posts);
        $view->set("count", $count);
        $view->set("page", $page);
        $view->set("limit", $limit);
    }

    /**
     * @before _secure, changeLayout
     */
    public function create() {
        $this->seo(array("title" => "Create Blog Post", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "addPost") {
            $post = new Post(array(
                "title" => RequestMethods::post("title"),
                "content" => RequestMethods::post("content")
            ));
            $post->save();
            self::redirect("/blog/all");
        }
    }

    /**
     * @before _secure, changeLayout
     */
    public function edit($id) {
        $this->seo(array("title" => "Create Blog Post", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $post = Post::first(array("id = ?" => $id));

        if (RequestMethods::post("action") == "savePost") {
            $post->title = RequestMethods::post("title");
            $post->content = RequestMethods::post("content");
            $post->save();

            $view->set("success", true);
        }
        $view->set("post", $post);
    }

    public function post($title, $id) {
        $view = $this->getActionView();
        if (!$id) {
            self::redirect("/404");
        }

        $post = \Post::first(array("id = ?" => $id, "live = ?" => true));
        if (!$post) {
            self::redirect("/404");
        }
        $this->seo(array("title" => "Blog | Post - ". $title, "view" => $this->getLayoutView()));
        $downloads = \Download::all(array("live = ?" => true), array("count", "strack_id", "id"), "count", "desc", 10, 1);

        $view->set("post", $post);
        $view->set("results", $downloads);
    }

}