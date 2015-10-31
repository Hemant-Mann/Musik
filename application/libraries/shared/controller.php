<?php

/**
 * Subclass the Controller class within our application.
 *
 * @author Faizan Ayubi
 */

namespace Shared {

    use Framework\Events as Events;
    use Framework\Router as Router;
    use Framework\Registry as Registry;
    use Framework\ArrayMethods as ArrayMethods;

    class Controller extends \Framework\Controller {

        /**
         * @readwrite
         */
        protected $_user;
        
        /**
         * @protected
         */
        public function _admin() {
            if (!$this->user->admin) {
                throw new Router\Exception\Controller("Not a valid admin user account");
            }
        }

        public function seo($params = array()) {
            $seo = Registry::get("seo");
            foreach ($params as $key => $value) {
                $property = "set" . ucfirst($key);
                $seo->$property($value);
            }
            $params["view"]->set("seo", $seo);
        }

        public function noview() {
            $this->willRenderLayoutView = false;
            $this->willRenderActionView = false;
        }

        public function JSONview() {
            $this->willRenderLayoutView = false;
            $this->defaultExtension = "json";
        }

        /**
         * @protected
         */
        public function _secure() {
            $user = $this->getUser();
            if (!$user) {
                header("Location: /login");
                exit();
            }
        }

        public static function redirect($url) {
            header("Location: {$url}");
            exit();
        }

        public function setUser($user) {
            $session = Registry::get("session");
            if ($user) {
                $session->set("user", $user->id);
            } else {
                $session->erase("user");
            }
            $this->_user = $user;
            return $this;
        }

        public function __construct($options = array()) {
            parent::__construct($options);

            // connect to database
            $database = Registry::get("database");
            $database->connect();

            // schedule: load user from session           
            Events::add("framework.router.beforehooks.before", function($name, $parameters) {
                $session = Registry::get("session");
                $controller = Registry::get("controller");
                $user = $session->get("user");
                if ($user) {
                    $controller->user = \User::first(array("id = ?" => $user));
                }
            });

            // schedule: save user to session
            Events::add("framework.router.afterhooks.after", function($name, $parameters) {
                $session = Registry::get("session");
                $controller = Registry::get("controller");
                if ($controller->user) {
                    $session->set("user", $controller->user->id);
                }
            });

            // schedule: disconnect from database
            Events::add("framework.controller.destruct.after", function($name) {
                $database = Registry::get("database");
                $database->disconnect();
            });
        }

        /**
         * Checks whether the user is set and then assign it to both the layout and action views.
         */
        public function render() {
            /* if the user and view(s) are defined, 
             * assign the user session to the view(s)
             */
            $session = Registry::get("session");
            $tracks = $session->get('User:$pListTracks');
            $current = $session->get('Users:$currentPlaylist');
            $playlists = $session->get('User:$playlists');
            
            if ($this->user) {
                if ($this->actionView) {
                    $key = "user";
                    if ($this->actionView->get($key, false)) {
                        $key = "__user";
                    }
                    $this->actionView->set($key, $this->user);

                    if ($tracks) {
                        $this->actionView->set('current', $current);
                        $this->actionView->set('playlists', $playlists);
                        $this->actionView->set('plistTracks', $tracks);
                    }
                }
                if ($this->layoutView) {
                    $key = "user";
                    if ($this->layoutView->get($key, false)) {
                        $key = "__user";
                    }
                    $this->layoutView->set($key, $this->user);

                    if ($tracks) {
                        $this->layoutView->set('current', $current);
                        $this->layoutView->set('playlists', $playlists);
                        $this->layoutView->set('plistTracks', $tracks);
                    }                    
                }
            }

            parent::render();
        }

        protected function generateSalt($length = 22) {
            //Not 100% unique, not 100% random, but good enought for a salt
            //MD5 returns 32 characters
            $unique_random_string = md5(uniqid(mt_rand(), true));

            //valid characters for a salt are [a-z A-Z 0-9 ./]
            $base64_string = base64_encode($unique_random_string);

            //but not '+' which is in base64 encoding
            $modified_base64_string = str_replace('+', '.', $base64_string);

            //Truncate string to the correct length
            $salt = substr($modified_base64_string, 0, $length);

            return $salt;
        }

        protected function setPagination($pageRoot, $current, $start = 1, $end = 5) {
            $count = array();
            for ($i = $start; $i <= $end; ++$i) {
                $count[] = $i;
            }
            $pagination = array(
                'pageRoot' => $pageRoot,
                'count' => $count,
                'firstPage' => $start,
                'currentPage' => $current,
                'lastPage' => $end
            );
            $pagination = ArrayMethods::toObject($pagination);
            return $pagination;
        }

        protected function seoOptimize() {
            $seo = array();
            $seo["keywords"] = "music, music free, music mp3, videos music, search music, free mp3 download, free music download, music listen online, online music player, best music player, music lyrics, new music, play music, top tracks, top artists, discover tracks, music lovers, make playlist, share playlist, share tracks on facebook";

            $seo["description"] = "A Website made for music lovers. Listen to the latest music, tracks by top artists, search for music, lyrics, songs, or videos. Take the music experience to the next level with our online music player. Find details of any artist or track and save your favorite tracks in our custom playlist. Share the playlist with your friends on facebook.";

            return $seo;
        }

    }

}
