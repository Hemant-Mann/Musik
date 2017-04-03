<?php

ob_start();
define("DEBUG", TRUE);
define("APP_PATH", dirname(__FILE__));
define("CDN", "/public/assets/");

try {
    
    // library's class autoloader
    spl_autoload_register(function($class) {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
        $file = APP_PATH . "/application/libraries/{$path}.php";

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    });

    // Google Library Autoloader
    spl_autoload_register(function($className) {
        $classPath = explode('_', $className);
        if ($classPath[0] != 'Google') {
            return;
        }
        // Drop 'Google', and maximum class file path depth in this project is 3.
        $classPath = array_slice($classPath, 1, 2);

        $filePath = APP_PATH . '/application/libraries/google-api-php-client/src/Google/' . implode('/', $classPath) . '.php';
        if (file_exists($filePath)) {
            require_once($filePath);
        }
    });

    // 2. load the Core class that includes an autoloader
    require(APP_PATH . "/framework/core.php");
    Framework\Core::initialize();

    // plugins
    $path = APP_PATH . "/application/plugins";
    $iterator = new DirectoryIterator($path);

    foreach ($iterator as $item) {
        if (!$item->isDot() && $item->isDir()) {
            include($path . "/" . $item->getFilename() . "/initialize.php");
        }
    }

    // 3. load and initialize the Configuration class 
    $configuration = new Framework\Configuration(array(
        "type" => "ini"
    ));
    Framework\Registry::set("configuration", $configuration->initialize());

    // 4. load and initialize the Database class – does not connect
    $database = new Framework\Database();
    Framework\Registry::set("database", $database->initialize());

    // 5. load and initialize the Cache class – does not connect
    $cache = new Framework\Cache();
    Framework\Registry::set("cache", $cache->initialize());

    // 6. load and initialize the Session class 
    $session = new Framework\Session();
    Framework\Registry::set("session", $session->initialize());

    if (php_sapi_name() !== 'cli') {
        throw new Framework\Router\Exception\Controller("Invalid action", 1);
    }
    
    // 7. load the Router class and provide the url + extension
    $c = (isset($argv[1])) ? $argv[1] : "admin";
    $a = (isset($argv[2])) ? $argv[2] : "install";
    $router = new Framework\Router(array(
        "url" => "$c/$a",
        "extension" => !empty($_GET["extension"]) ? $_GET["extension"] : "html"
    ));
    Framework\Registry::set("router", $router);

    // include custom routes 
    include("public/routes.php");

    // 8. dispatch the current request 
    $router->dispatch();

    // 9. unset global variables
    unset($configuration);
    unset($database);
    unset($cache);
    unset($session);
    unset($router);
} catch (Exception $e) {
    
    // list exceptions
    $exceptions = array(
        "500" => array(
            "Framework\Cache\Exception",
            "Framework\Cache\Exception\Argument",
            "Framework\Cache\Exception\Implementation",
            "Framework\Cache\Exception\Service",
            
            "Framework\Configuration\Exception",
            "Framework\Configuration\Exception\Argument",
            "Framework\Configuration\Exception\Implementation",
            "Framework\Configuration\Exception\Syntax",
            
            "Framework\Controller\Exception",
            "Framework\Controller\Exception\Argument",
            "Framework\Controller\Exception\Implementation",
            
            "Framework\Core\Exception",
            "Framework\Core\Exception\Argument",
            "Framework\Core\Exception\Implementation",
            "Framework\Core\Exception\Property",
            "Framework\Core\Exception\ReadOnly",
            "Framework\Core\Exception\WriteOnly",
            
            "Framework\Database\Exception",
            "Framework\Database\Exception\Argument",
            "Framework\Database\Exception\Implementation",
            "Framework\Database\Exception\Service",
            "Framework\Database\Exception\Sql",
            
            "Framework\Model\Exception",
            "Framework\Model\Exception\Argument",
            "Framework\Model\Exception\Connector",
            "Framework\Model\Exception\Implementation",
            "Framework\Model\Exception\Primary",
            "Framework\Model\Exception\Type",
            "Framework\Model\Exception\Validation",
            
            "Framework\Request\Exception",
            "Framework\Request\Exception\Argument",
            "Framework\Request\Exception\Implementation",
            "Framework\Request\Exception\Response",
            
            "Framework\Router\Exception",
            "Framework\Router\Exception\Argument",
            "Framework\Router\Exception\Implementation",
            
            "Framework\Session\Exception",
            "Framework\Session\Exception\Argument",
            "Framework\Session\Exception\Implementation",
            
            "Framework\Template\Exception",
            "Framework\Template\Exception\Argument",
            "Framework\Template\Exception\Implementation",
            "Framework\Template\Exception\Parser",
            
            "Framework\View\Exception",
            "Framework\View\Exception\Argument",
            "Framework\View\Exception\Data",
            "Framework\View\Exception\Implementation",
            "Framework\View\Exception\Renderer",
            "Framework\View\Exception\Syntax"
        ),
        "404" => array(
            "Framework\Router\Exception\Action",
            "Framework\Router\Exception\Controller"
        )
    );

    $exception = get_class($e);

    // attempt to find the approapriate template, and render
    foreach ($exceptions as $template => $classes) {
        foreach ($classes as $class) {
            if ($class == $exception) {
                header("Content-type: text/html");
                include(APP_PATH . "/application/views/errors/{$template}.php");
                exit;
            }
        }
    }

    // log or email any error
    
    // render fallback template
    header("Content-type: text/html");
    include(APP_PATH . "/application/views/errors/500.php");
    exit;
}


if (isset($argv[3])) {
	$apiKey = $argv[3];
	$lastFmConfig = APP_PATH . "/application/plugins/lastfm/initialize.php";
	$content = "<?php
use LastFm\Src\Caller\CallerFactory as CallerFactory;
CallerFactory::getDefaultCaller()->setApiKey('$apiKey');";

	file_put_contents($lastFmConfig, $content);
}

if (isset($argv[4])) {
	$apiKey = $argv[4];
	$googleConfig = APP_PATH . "/application/plugins/google/initialize.php";
	$content = '<?php
$client_id = "<Client Id>";
$client_secret = "<Client Secret>";
$developer_key = "'.$apiKey.'";

$client = new Google_Client();
$client->setDeveloperKey($developer_key);
$youtube = new Google_Service_YouTube($client);

Framework\Registry::set("gClient", $client);
Framework\Registry::set("youtube", $youtube);';

	file_put_contents($googleConfig, $content);
}