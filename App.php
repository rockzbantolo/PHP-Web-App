<?php
require __DIR__ . '/composer/vendor/autoload.php';

use Mws\Middleware\Auth;
use Mws\Middleware\Cors;
use Mws\Helpers\Env;
use Mws\Helpers\Response;
use Mws\Helpers\Request;
use Mws\Core\Routes;

Env::InitEnv('./.env');
Cors::InitCors();
Auth::InitApiKeyAuth();

// Initialize router
[$router, $method, $path] = Request::initRouter();

// Load routes
Routes::initRoutes($router);

// Resolve request
try {
    $router->resolve($method, $path);
} catch (Throwable $e) {
    Response::error("Unhandled Exception", 500, $e);
}
