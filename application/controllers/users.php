<?php

/**
 * User Controller: Handles user login/signup and related functions
 *
 * @author Hemant Mann
 */
use Shared\Controller as Controller;
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;
use Framework\StringMethods as StringMethods;

class Users extends Controller {

    /**
     * @before _secure
     */
    public function profile() {
        $view = $this->getActionView();

        $content = array();

        // find all the playlists
        $playlists = Playlist::all(array("user_id = ?" => $this->user->id, "live = ?" => true), array("id", "name", "genre", "view", "created"));
        foreach ($playlists as $p) {
            $count = PlaylistTrack::count(array("playlist_id = ?" => $p->id));
            $content["playlists"][] = array(
                "id" => $p->id,
                "name" => $p->name,
                "genre" => $p->genre,
                "view" => $p->view,
                "tracks" => $count,
                "created" => StringMethods::only_date($p->created)
            );
        }

        // find the recent activity
        // @todo => Display what tracks the user added last time
        $content = ArrayMethods::toObject($content);
        $view->set("content", $content);
    }

    public function login() {
        if ($this->user){
            self::redirect("/profile");
        }
        $this->getLayoutView()->set("change", true);
        $view = $this->getActionView();
        $session = Registry::get("session");

        if (RequestMethods::post("action") == "login" && RequestMethods::post("token") === $session->get('Users\Login:$token')) {
            $password = RequestMethods::post("password");
            $email = RequestMethods::post("email");

            $user = User::first(array("email = ?" => $email, "live = ?" => true));

            if ($user) {
                if ($this->passwordCheck($password, $user->password)) {
                    $this->setUser($user);	// successful login
                    $this->setPlaylists(true);
                    self::redirect("/profile");
                } else {
                    $error = "Invalid email/password";
                }
            } else {
                $error = "Invalid email/password";
            }
            $view->set("error", $error);
        }
        // Securing login
        $token = $this->generateSalt();
        $view->set("token", $token);
        $session->set('Users\Login:$token', $token);
    }

    public function signup() {
        if ($this->user) {
            self::redirect("/profile");
        }
        $this->getLayoutView()->set("change", true);
        $view = $this->getActionView();
        $session = Registry::get("session");

        if (RequestMethods::post("action") == "signup" && RequestMethods::post("token") === $session->get('Users\Login:$token')) {
            $password = RequestMethods::post("password");
            $email = RequestMethods::post("email");

            if (RequestMethods::post("confirm") != $password) {
                $view->set("message", "Passwords do not match!");
            } else {
                $user = User::first(array("email = ?" => $email));

                if ($user) {
                    $view->set("message", "Email already registered");
                } else {
                    $user = new User(array(
                        "name" => RequestMethods::post("name"),
                        "email" => $email,
                        "password" => $this->encrypt($password),
                        "admin" => false,
                        "live" => true,
                        "deleted" => false
                    ));
                    $user->save();
                    $view->set("message", 'You are registered!! Please <a href="/login">Login</a> to continue');    
                }
            }
        }
        $token = $this->generateSalt();
        $view->set("token", $token);
        $session->set('Users\Login:$token', $token);
    }

    public function logout() {
        $this->setUser(false);
        self::redirect("/login");
    }

    /**
     * @before _secure
     */
    public function savePlaylist() {
        $this->noview();

        if (RequestMethods::post("action") == "savePlaylist" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
            try {
                $playlist = RequestMethods::post("playlist");
                $id = RequestMethods::post("playlistId");
                
                foreach ($playlist as $p) {
                    if ($p["isSaved"] == "false" && $p["deleted"] == "false") {
                        $track = SavedTrack::first(array("yid = ?" => $p["yid"]), array("id"));

                        if (!$track) {
                            $track = new SavedTrack(array(
                                "track" => $p["track"],
                                "mbid" => $p["mbid"],
                                "artist" => $p["artist"],
                                "yid" => $p["yid"],
                            ));
                            $track->save();
                        }
                        $plist = new PlaylistTrack(array(
                            "playlist_id" => $id,
                            "strack_id" => $track->id,
                            "play_count" => 0
                        ));
                        $plist->save();    
                    } else if ($p["deleted"] == "true" && $p["ptrackid"] != "false") {
                        $ptrack = PlaylistTrack::first(array("id = ?" => $p["ptrackid"]));
                        $ptrack->live = false;
                        $ptrack->save();
                    }
                }
                $this->setTracks($id);
                echo "Success";
            } catch (\Exception $e) {
                echo "Error";
            }
        } else {
            self::redirect("/404"); // prevent direct access
        }
    }

    /**
     * @before _secure
     */
    public function updatePlaylist($id) {
        if (!$id || empty($id)) {
            self::redirect("/404");
        }

        if (RequestMethods::post("action") == "updatePlaylist") {
            $time = RequestMethods::post("modified");
            $track = RequestMethods::post("track");

            $plistTrack = PlaylistTrack::first(array("playlist_id = ?" => $id, "track = ?" => $track));
            if ($plistTrack) {
                $plistTrack->modified = $time;
                $plistTrack->play_count++;
                $plistTrack->save();
            } else {
                return;
            }
        } else {
            self::redirect("/404");
        }
    }

    /**
     * @before _secure
     */
    public function createPlaylist($name, $genre = "default") {
        $name = (empty($name)) ? RequestMethods::post("name") : $name;
        $session = Registry::get("session");

        $return = $session->get('Users\setPlaylists:$internal');
        if (empty($name)) {
            self::redirect("/404");
        }

        if (RequestMethods::post("action") == "createPlaylist" || $return) {
            $newPlaylist = new Playlist(array(
                "name" => $name,
                "user_id" => $this->user->id,
                "genre" => RequestMethods::post("genre", $genre),
                "view" => RequestMethods::post("view", "private")
            ));
            $newPlaylist->save();
            $this->setPlaylists();

            if ($return) {
                return $newPlaylist;    
            } else {
                self::redirect("/profile");
            }

        } else {
            self::redirect("/404");
        }
    }

    /**
     * @before _secure
     */
    protected function setPlaylists($alsoSetCurrent = false) {
        $session = Registry::get("session");
        $current = false;

        // find all the playlists of the user
        $playlists = Playlist::all(array("user_id = ?" => $this->user->id, "live = ?" => true), array("name", "id", "user_id", "genre", "view"), "created", "desc");
        if (!$playlists || empty($playlists)) {
            $session->set('Users\setPlaylists:$internal', true);
            $playlists = array($this->createPlaylist("Playlist 1"));
            $session->erase('Users\setPlaylists:$internal');
        }
        $current = $playlists[0]->id;

        $plist = array();
        foreach ($playlists as $p) {
            $plist[] = array(
                "id" => $p->id,
                "name" => $p->name,
                "user_id" => $p->user_id,
                "genre" => $p->genre,
                "view" => $p->view
            );
        }
        $plist = ArrayMethods::toObject($plist);
        $session->set('User:$playlists', $plist);
        
        if ($alsoSetCurrent) {
            $this->setCurrentPlaylist($current, $playlists[0]);
        }
    }

    protected function setCurrentPlaylist($id, $object = false) {
        $session = Registry::get("session");

        if (!$object) {
            $p = Playlist::first(array("id = ?" => $id, "live = ?" => true), array("user_id","name", "genre", "view"));    
        } else {
            $p = $object;
        }
        

        $playlist = array();
        $playlist["id"] = $id;
        $playlist["user_id"] = $p->user_id;
        $playlist["name"] = $p->name;
        $playlist["genre"] = $p->genre;
        $playlist["view"] = $p->view;
        $playlist = ArrayMethods::toObject($playlist);

        $session->set('Users:$currentPlaylist', $playlist);
        $this->setTracks($id);
    }

    /**
     * @before _secure
     */
    protected function setTracks($playlistId) {
        $session = Registry::get("session");
        $tracks = array();
        $playlistTracks = PlaylistTrack::all(array("playlist_id = ?" => $playlistId, "live = ?" => true), array("id" ,"strack_id"));
        foreach ($playlistTracks as $t) {
            $track = SavedTrack::first(array("id = ?" => $t->strack_id));
            $tracks[] = array(
                "ptrackid" => $t->id,
                "track" => $track->track,
                "artist" => $track->artist,
                "mbid" => $track->mbid,
                "yid" => $track->yid,
                "dbid" => $track->id
            );
        }
        $tracks = ArrayMethods::toObject($tracks);
        $session->set('User:$pListTracks', $tracks);
    }

    /**
     * @before _secure
     */
    public function changePlaylist() {
        if (RequestMethods::post("action") == "changePlaylist") {
            $id = RequestMethods::post("id");

            $this->setCurrentPlaylist($id);
            $to = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "/";
            self::redirect($to);
        } else {
            self::redirect("/404");
        }
    }

    /**
     * @before _secure
     */
    public function editPlaylist() {
        $this->noview();
        if (RequestMethods::post("action") == "editPlaylist") {
            $id = RequestMethods::post("id");

            if ($id) {
                $playlist = Playlist::first(array("id = ?" => $id));
                if (!$playlist) {
                    return;
                }

                $playlist->name = RequestMethods::post("name");
                $playlist->genre = RequestMethods::post("genre", "default");
                $playlist->view = RequestMethods::post("view", "private");
                $playlist->save();

                $this->setCurrentPlaylist($id, $playlist);

                $to = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "/";
                self::redirect($to);
            }
        } else {
            self::redirect("/404");
        }
    }

    public function fbLogin() {
        $this->noview();
        $session = Registry::get("session");

        if ((RequestMethods::post("action") == "fbLogin") && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') && (RequestMethods::post("token") == $session->get('Users\Login:$token'))) {
            // process the registration
            $email = RequestMethods::post("email");

            $user = User::first(array("email = ?" => $email, "live = ?" => true));

            if (!$user) {
                $pass = $this->generateSalt();
                $user = new User(array(
                    "name" => RequestMethods::post("name"),
                    "email" => $email,
                    "password" => $this->encrypt($pass),
                    "admin" => false
                ));
                $user->save();
            }
            $this->setUser($user);
            $this->setPlaylists(true);
            echo "Success";
        } else {
            self::redirect("/404");
        }
    }

    /**
     * Encrypts the password using blowfish algorithm
     */
    protected function encrypt($password) {
        $hash_format = "$2y$10$";  //tells PHP to use Blowfish with a "cost" of 10
        $salt_length = 22; //Blowfish salts should be 22-characters or more
        $salt = $this->generateSalt($salt_length);
        $format_and_salt = $hash_format . $salt;
        $hash = crypt($password, $format_and_salt);
        return $hash;
    }

    /**
     * Checks the password by hashing it using the existing hash
     */
    protected function passwordCheck($password, $existingHash) {
        //existing hash contains format and salt or start
        $hash = crypt($password, $existingHash);
        if ($hash == $existingHash) {
            return true;
        } else {
            return false;
        }
    }
}
