<?php
namespace Mws\Core;

use Mws\Helpers\Response;

class Https
{
    private $routes = [];

    public function register($method, $path, $handler)
    {
        $this->routes[strtoupper($method)]['/' . trim($path, '/')] = $handler;
    }

    public function resolve($method, $path)
    {
        $method = strtoupper($method);
        $normalizedPath = '/' . trim($path, '/');

        if (!isset($this->routes[$method][$normalizedPath])) {
            return Response::error("Route not found", 404);
        }

        [$controller, $action] = $this->routes[$method][$normalizedPath];

        if (!class_exists($controller) || !method_exists($controller, $action)) {
            return Response::error("Handler not found", 500);
        }

        $instance = new $controller();
        $input = in_array($method, ['POST', 'PUT', 'DELETE']) ? json_decode(file_get_contents("php://input"), true) ?? [] : [];

        return $instance->$action($input);
    }    
}
