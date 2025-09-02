<?php
require __DIR__ . '/vendor/autoload.php';

use Mws\Middleware\Auth;
use Mws\Middleware\Cors;
use Mws\Helpers\Env;
use Mws\Helpers\Request;
use Mws\Router\Routes;


Env::InitEnv('./.env');
Cors::InitCors();
Auth::InitAuth();

[$router, $method, $path] = Request::initRouter();
Routes::setRoutes($router);

echo "Class Loaded!" . PHP_EOL;
echo "Method: " . $method . PHP_EOL;
echo "Final Path: " . $path . PHP_EOL;
echo "Router Object:" . PHP_EOL;
print_r($router);
exit;
