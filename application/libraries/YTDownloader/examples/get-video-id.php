<?php

require '../autoloader.php';

$url = 'https://www.youtube.com/watch?v=oeCihv9A3ac';

$id = YTDownloader\Helper::getVideoId($url);

var_dump($id);

?>