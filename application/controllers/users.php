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

class Users extends Controller {

    public function profile() {
        
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

            $user = User::first(array("email = ?" => $email));

            if ($user) {
                if ($this->passwordCheck($password, $user->password)) {
                    $this->setUser($user);	// successful login
                    $this->setPlaylist();
                    self::redirect("/profile");
                } else {
                    $error = "Invalid username/password";
                }
            } else {
                $error = "Invalid username/password";
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

            $user = new User(array(
                "name" => RequestMethods::post("name"),
                "email" => RequestMethods::post("email"),
                "password" => $this->encrypt($password),
                "admin" => false,
                "live" => true,
                "deleted" => false
            ));

            if (RequestMethods::post("confirm") != $password) {
                $view->set("message", "Passwords do not match!");
            } else {
                $user->save();
                $view->set("message", 'You are registered!! Please <a href="/login">Login</a> to continue');
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
                
                foreach ($playlist as $p) {
                    if ($p["isSaved"] == "false") {
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
                        $plist = new Playlist(array(
                            "user_id" => $this->user->id,
                            "strack_id" => $track->id
                        ));
                        $plist->save();    
                    }
                }
                $this->setPlaylist();
                echo "Success";
            } catch (\Exception $e) {
                echo "Error";
            }
        } else {
            self::redirect("/404"); // prevent direct access
        }
    }

    public function fbLogin() {
        $this->noview();
        $session = Registry::get("session");

        if ((RequestMethods::post("action") == "fbLogin") && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') && (RequestMethods::post("token") == $session->get('Users\Login:$token'))) {
            // process the registration
            $email = RequestMethods::post("email");

            $user = User::first(array("email = ?" => $email));

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
            $this->setPlaylist();
            echo "Success";
        } else {
            self::redirect("/404");
        }
    }

    protected function setPlaylist() {
        $playlists = Playlist::all(array("user_id = ?" => $this->user->id, "live = ?" => true), array("strack_id"));
        if (!$playlists) {
            return;
        }

        $session = Registry::get("session");
        $tracks = array();
        foreach ($playlists as $p) {
            $track = SavedTrack::first(array("id = ?" => $p->strack_id));
            $tracks[] = array(
                "track" => $track->track,
                "artist" => $track->artist,
                "mbid" => $track->mbid,
                "yid" => $track->yid,
                "dbid" => $track->id
            );
        }
        $tracks = ArrayMethods::toObject($tracks);
        $session->set('Users:$playlist', $tracks);
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
