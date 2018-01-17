# php-git-server

DAV based PHP based Git server. No dependencies on git command.

## Install

Install latest version using [composer](https://getcomposer.org/).

```
$ composer require rcs_us/php-git-server
```

## Usage

The server extends sabre.io's DAV server(http://sabre.io/dav/) and nikic's FastRoute (https://github.com/nikic/FastRoute).

A basic implementation would look like the following:

```php

if (PHP_SAPI == "cli-server") {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

// Define source path ( built in PHP server doesn't like relative paths )
defined('SOURCE_PATH')
|| define('SOURCE_PATH', (getenv('SOURCE_PATH') ? getenv('SOURCE_PATH') : dirname(__DIR__)));

defined('REPOSITORY_PATH')
|| define('REPOSITORY_PATH', (getenv('REPOSITORY_PATH') ? getenv('REPOSITORY_PATH') : "/tmp/git-repositories"));

$loader = require SOURCE_PATH . "/vendor/autoload.php";

$rcsGitServer = new \RCS\Git\Server();

$serverAuthBackend = new \Sabre\DAV\Auth\Backend\BasicCallBack(function($user, $pass){
    $args = [$user, $pass];
    error_log("args: " . print_r($args, true) );
    return true;
});

$serverAuthPlugin  = new \Sabre\DAV\Auth\Plugin($serverAuthBackend);
   
// OPTIONALLY, you can add authentication this way     
// $rcsGitServer->addServerPlugin($serverAuthPlugin);

$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {

    // This can also be a single addRoute(['PROPFIND','MKCOL',....])
    $r->addRoute('PROPFIND', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('MKCOL', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('LOCK', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('PUT', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('UNLOCK', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('GET', '/{repository}.git/[{path:.+}]', 'webdav');
    $r->addRoute('MOVE', '/{repository}.git/[{path:.+}]', 'webdav');
    
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        error_log("\FastRoute\Dispatcher::NOT_FOUND");
        // ... 404 Not Found
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        error_log("\FastRoute\Dispatcher::METHOD_NOT_ALLOWED");
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case \FastRoute\Dispatcher::FOUND:
        error_log("\FastRoute\Dispatcher::FOUND");
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // ... call $handler with $vars
        
        // list($class, $method) = explode(":", $handler, 2);
        // call_user_func_array(array(new $class, $method), $vars);
        call_user_func(array($rcsGitServer, $handler), $vars);
        
        break;
}

// So you can see the requests as they come in on the PHP built in server
if (PHP_SAPI == "cli-server") {
    error_log($_SERVER["REQUEST_METHOD"] . "::" . $_SERVER["REQUEST_URI"]."\n");
}

```

This can be tested with PHP's built in server using ( you can substitute 8888 for whatever port you want ):

```php
php -S localhost:8888 -t public index.php
```

