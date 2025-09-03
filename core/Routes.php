<?php
namespace Mws\Core;

use ReflectionClass;

interface RouteControllerInterface
{
    public static function initRoutes($router);
}

class Routes
{
    public static function initRoutes($router)
    {
        $controllersPath = __DIR__ . '/../Controllers/';

        foreach (glob($controllersPath . '*.php') as $file) {
            $className = 'Mws\\Controllers\\' . basename($file, '.php');

            if (!class_exists($className)) {
                error_log("Controller class $className does not exist");
                continue;
            }

            try {
                $ref = new ReflectionClass($className);

                // Only register classes that implement RouteControllerInterface
                if ($ref->implementsInterface(RouteControllerInterface::class)) {
                    $className::initRoutes($router);
                } else {
                    error_log("Controller $className does not implement RouteControllerInterface");
                }

            } catch (\Throwable $e) {
                error_log("Failed to register routes for $className: " . $e->getMessage());
            }
        }
    }
}
