<?php
namespace Mws\Router;

use Mws\Controllers\UserController;

class Routes
{
    public static function setRoutes($router)
    {
        // Initialize all controllers' routes
        UserController::initRoutes($router);

        // Add more controllers here if needed
        // e.g., ProductController::initRoutes($router);
    }
}
