<?php
namespace Mws\Core;

use Mws\Controllers\AuthController;
use Mws\Controllers\UserController;
use Mws\Controllers\StoreController;
use Mws\Controllers\RoleController;
use Mws\Controllers\ProductController;

class Routes
{
    public static function initRoutes($router)
    {
        // 🔹 AUTH ROUTE
        self::mapRoutes($router, AuthController::class, '/Auth', [
            ['POST', '/Login',        'login'],
            ['POST', '/Register',     'register'],
            ['POST', '/Logout',       'logout'],
            ['POST', '/RefreshToken', 'refreshToken'],
        ]);

        // 🔹 STORE ROUTE
        self::mapRoutes($router, StoreController::class, '/Stores', [
            ['GET',    '',        'getAllStores'],
            ['GET',    '/{id}',   'getStoreById'],
            ['POST',   '',        'saveStore'],
            ['PUT',    '/{id}',   'updateStore'],
            ['DELETE', '/{id}',   'deleteStore'],
            ['POST',   '/Batch',  'batchCreateStores'],
            ['PUT',    '/Batch',  'batchUpdateStores'],
            ['DELETE', '/Batch',  'batchDeleteStores'],
        ]);

        // 🔹 ROLE ROUTE
        self::mapRoutes($router, RoleController::class, '/Role', [
            ['GET',    '',        'getAllRoles'],
            ['GET',    '/{id}',   'getRoleById'],
            ['POST',   '',        'saveRoles'],
            ['PUT',    '',        'updateRoles'],
            ['DELETE', '',        'deleteRoles'],
        ]);

        // 🔹 PRODUCTS ROUTE
        self::mapRoutes($router, ProductController::class, '/Product', [
            ['GET',    '',        'getAllProducts'],
            ['GET',    '/{id}',   'getProductById'],
            ['POST',   '',        'saveProducts'],
            ['PUT',    '/{id}',   'updateProducts'],
            ['DELETE', '/{id}',   'deleteProducts'],
        ]);

        // 🔹 USER ROUTE
        self::mapRoutes($router, UserController::class, '/Users', [
            ['GET',    '',        'getAllUsers'],
            ['GET',    '/{id}',   'getUserById'],
            ['POST',   '',        'saveUser'],
            ['PUT',    '/{id}',   'updateUserById'],
            ['DELETE', '/{id}',   'deleteUserById'],
            ['POST',   '/Batch',  'batchCreateUsers'],
            ['PUT',    '/Batch',  'batchUpdateUsers'],
            ['DELETE', '/Batch',  'batchDeleteUsers'],
        ]);
    }

    private static function mapRoutes($router, string $controller, string $prefix, array $routes)
    {
        foreach ($routes as [$method, $path, $action]) {
            $router->register($method, $prefix . $path, [$controller, $action]);
        }
    }
}
