<?php
namespace Mws\Router;

use Mws\Controllers\UserController;
use Mws\Controllers\RoleController;
use Mws\Controllers\ProductController;
use Mws\Controllers\StoreController;

class Routes
{
    public static function setRoutes($router)
    {
        // Initialize all controllers' routes
        UserController::initRoutes($router);
        RoleController::initRoutes($router);
        ProductController::initRoutes($router);
        StoreController::initRoutes($router);
        
        // Add more controllers here if needed
        // e.g., ProductController::initRoutes($router);
    }
}
