<?php

/**
 * User Controller: Handles user login/signup and related functions
 *
 * @author Hemant Mann
 */
use Shared\Controller as Controller;
use Framework\RequestMethods as RequestMethods;

class Users extends Controller {

    public function profile() {
        
    }

    public function signin() {
        if ($this->user){
            self::redirect("/profile");
        }
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "signin") {
            $password = RequestMethods::post("password");
            $email = RequestMethods::post("email");

            $user = User::first(array("email = ?" => $email));

            if ($user) {
                if ($this->passwordCheck($password, $user->password)) {
                    $this->setUser($user);	// successful login
                    self::redirect("/profile");
                } else {
                    $error = "Invalid username/password";
                }
            } else {
                $error = "Invalid username/password";
            }
            $view->set("error", $error);
        }
    }

    public function signup() {
        if ($this->user) {
            self::redirect("/profile");
        }
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "signup") {
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
                $view->set("message", 'You are registered!! Please <a href="/signin">signin</a> to continue');
            }
        }
    }

    public function logout() {
        $this->setUser(false);
        self::redirect("/login");
    }

    /**
     * Generates a salt for hashing the password
     */
    private function generateSalt($length) {
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
