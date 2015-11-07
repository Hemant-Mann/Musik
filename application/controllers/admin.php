<?php

/**
 * Description of admin
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;
use Framework\Registry as Registry;

class Admin extends Users {
    
    /**
     * @before _secure, changeLayout
     */
    public function index() {
        $this->seo(array("title" => "Dashboard", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $users = \User::count();
        $lyrics = \Lyric::count();
        $database = Registry::get("database");
        $downloads = $database->query()->from("downloads", array("SUM(count)" => "count"))->all();
        $ptracks = \PlaylistTrack::count();
        // var_dump($downloads[0]["count"]);
        $data = array(
            "users" => $users,
            "lyrics" => $lyrics,
            "ptracks" => $ptracks,
            "downloads" => $downloads[0]["count"]
        );
        $data = ArrayMethods::toObject($data);
        $view->set("data", $data);
    }
    
    /**
     * Searchs for data and returns result from db
     * @param type $model the data model
     * @param type $property the property of modal
     * @param type $val the value of property
     * @before _secure, changeLayout
     */
    public function search($model = NULL, $property = NULL, $val = 0, $page=1) {
        $this->seo(array("title" => "Search", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $model = RequestMethods::get("model", $model);
        $property = RequestMethods::get("key", $property);
        $val = RequestMethods::get("value", $val);
        $page = RequestMethods::get("page", $page);
        $sign = RequestMethods::get("sign", "equal");

        $view->set("items", array());
        $view->set("values", array());
        $view->set("model", $model);
        $view->set("page", $page);
        $view->set("property", $property);
        $view->set("val", $val);
        $view->set("sign", $sign);

        if ($model) {
            if($sign == "like"){
                $where = array("{$property} LIKE ?" => "%{$val}%");
            } else {
                $where = array("{$property} = ?" => $val);
            }
            
            $objects = $model::all($where,array("*"),"created", "desc", 10, $page);
            $count = $model::count($where);$i = 0;
            if ($objects) {
                foreach ($objects as $object) {
                    $properties = $object->getJsonData();
                    foreach ($properties as $key => $property) {
                        $key = substr($key, 1);
                        $items[$i][$key] = $property;
                        $values[$i][] = $key;
                    }
                    $i++;
                }
                $view->set("items", $items);
                $view->set("values", $values[0]);
                $view->set("count", $count);
                //echo '<pre>', print_r($values[0]), '</pre>';
                $view->set("success", "Total Results : {$count}");
            } else {
                $view->set("success", "No Results Found");
            }
        }
    }

    /**
     * Shows any data info
     * 
     * @before _secure, changeLayout
     * @param type $model the model to which shhow info
     * @param type $id the id of object model
     */
    public function info($model = NULL, $id = NULL) {
        $this->seo(array("title" => "{$model} info", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $items = array();
        $values = array();

        $object = $model::first(array("id = ?" => $id));
        $properties = $object->getJsonData();
        foreach ($properties as $key => $property) {
            $key = substr($key, 1);
            if (strpos($key, "_id")) {
                $child = ucfirst(substr($key, 0, -3));
                $childobj = $child::first(array("id = ?" => $object->$key));
                $childproperties = $childobj->getJsonData();
                foreach ($childproperties as $k => $prop) {
                    $k = substr($k, 1);
                    $items[$k] = $prop;
                    $values[] = $k;
                }
            } else {
                $items[$key] = $property;
                $values[] = $key;
            }
        }
        $view->set("items", $items);
        $view->set("values", $values);
        $view->set("model", $model);
    }

    /**
     * Updates any data provide with model and id
     * 
     * @before _secure, changeLayout
     * @param type $model the model object to be updated
     * @param type $id the id of object
     */
    public function update($model = NULL, $id = NULL) {
        $this->seo(array("title" => "Update", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $object = $model::first(array("id = ?" => $id));

        $vars = $object->columns;
        $array = array();
        foreach ($vars as $key => $value) {
            array_push($array, $key);
            $vars[$key] = htmlentities($object->$key);
        }
        if (RequestMethods::post("action") == "update") {
            foreach ($array as $field) {
                $object->$field = RequestMethods::post($field, $vars[$field]);
                $vars[$field] = htmlentities($object->$field);
            }
            $object->save();
            $view->set("success", true);
        }

        $view->set("vars", $vars);
        $view->set("array", $array);
        $view->set("model", $model);
        $view->set("id", $id);
    }

    /**
     * Deletes any model with given id
     * 
     * @before _secure, changeLayout
     * @param type $model the model object to be deleted
     * @param type $id the id of object to be deleted
     */
    public function delete($model = NULL, $id = NULL) {
        $view = $this->getActionView();
        $this->JSONview();
        
        $object = $model::first(array("id = ?" => $id));
        $object->delete();
        $view->set("deleted", true);
        
        self::redirect($_SERVER['HTTP_REFERER']);
    }

    public function sync($model) {
        $this->noview();
        $db = Framework\Registry::get("database");
        $db->sync(new $model);
    }
    /**
     * @before _secure
     */
    public function fields($model = "user") {
        $this->noview();
        $class = ucfirst($model);
        $object = new $class;

        echo json_encode($object->columns);
    }
    
    public function changeLayout() {
        $this->defaultLayout = "layouts/admin";
        $this->setLayout();

        if ($this->user->admin != 1) {
            self::redirect("/404");
        }
    }

    protected function checkValidRequest($key, $location = "/admin") {
        if (empty($key) || !$key) {
            self::redirect($location);
        }
        return;
    }

}
