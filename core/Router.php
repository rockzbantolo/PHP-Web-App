<?php
namespace Mws\Core;

use Mws\Helpers\Response;
use Mws\Core\StatusCode;

class Router {
    
    private $routes = [];
    private $compiledRoutes = [];
    private $reflectionCache = [];

    public function register($method, $path, $handler)
    {
        $method = strtoupper($method);
        $normalizedPath = '/' . trim($path, '/');
        
        $this->routes[$method][$normalizedPath] = $handler;
        
        // Pre-compile regex and cache reflection data during registration
        if (strpos($normalizedPath, '{') !== false) {
            $this->compileRoute($method, $normalizedPath, $handler);
        }
    }

    private function compileRoute($method, $path, $handler)
    {
        // Pre-compile regex pattern
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';
        
        // Cache reflection data
        [$controller, $action] = $handler;
        $reflectionKey = $controller . '::' . $action;
        
        if (!isset($this->reflectionCache[$reflectionKey])) {
            if (class_exists($controller) && method_exists($controller, $action)) {
                $reflection = new \ReflectionMethod($controller, $action);
                $this->reflectionCache[$reflectionKey] = [
                    'paramCount' => $reflection->getNumberOfParameters(),
                    'parameters' => $reflection->getParameters()
                ];
            } else {
                $this->reflectionCache[$reflectionKey] = false;
            }
        }
        
        $this->compiledRoutes[$method][] = [
            'pattern' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'reflection' => $this->reflectionCache[$reflectionKey]
        ];
    }

    public function resolve($method, $path)
    {
        $method = strtoupper($method);
        $normalizedPath = '/' . trim($path, '/');

        // Fast exact match lookup (O(1))
        if (isset($this->routes[$method][$normalizedPath])) {
            [$controller, $action] = $this->routes[$method][$normalizedPath];
            return $this->executeController($controller, $action, $method, [], $normalizedPath);
        }

        // Dynamic route matching using pre-compiled routes
        if (isset($this->compiledRoutes[$method])) {
            foreach ($this->compiledRoutes[$method] as $route) {
                if (preg_match($route['regex'], $normalizedPath, $matches)) {
                    // Extract only named parameters (faster than array filtering)
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    
                    [$controller, $action] = $route['handler'];
                    return $this->executeController($controller, $action, $method, $params, $route['pattern']);
                }
            }
        }

        return Response::error("Route not found", StatusCode::NOT_FOUND);
    }

    private function executeController($controller, $action, $method, $params, $routePattern)
    {
        $reflectionKey = $controller . '::' . $action;
        
        // Use cached reflection data
        if (!isset($this->reflectionCache[$reflectionKey])) {
            if (!class_exists($controller) || !method_exists($controller, $action)) {
                return Response::error("Api Handler not found", StatusCode::INTERNAL_SERVER_ERROR);
            }
            
            $reflection = new \ReflectionMethod($controller, $action);
            $this->reflectionCache[$reflectionKey] = [
                'paramCount' => $reflection->getNumberOfParameters(),
                'parameters' => $reflection->getParameters()
            ];
        }

        $reflectionData = $this->reflectionCache[$reflectionKey];
        if ($reflectionData === false) {
            return Response::error("Api Handler not found", StatusCode::INTERNAL_SERVER_ERROR);
        }

        try {
            $instance = new $controller();
            
            // Parse input only once and only for methods that need it
            $input = null;
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $rawInput = file_get_contents("php://input");
                $input = $rawInput ? json_decode($rawInput, true) ?? [] : [];
            }

            return $this->callControllerMethod($instance, $action, $reflectionData, $params, $input, $method);
            
        } catch (\Throwable $e) {
            return Response::error("Controller execution failed: " . $e->getMessage(), StatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    private function callControllerMethod($instance, $action, $reflectionData, $params, $input, $method)
    {
        $paramCount = $reflectionData['paramCount'];
        
        if ($paramCount === 0) {
            return $instance->$action();
        }
        
        if ($paramCount === 1) {
            if (!empty($params)) {
                return $instance->$action(reset($params)); // reset() is faster than array_values()[0]
            }
            return $instance->$action($input ?? []);
        }
        
        // Multiple parameters - optimize for common patterns
        if ($paramCount === 2 && in_array($method, ['PUT', 'PATCH', 'POST'])) {
            // Common pattern: method($id, $data)
            $paramValues = array_values($params);
            if (!empty($paramValues)) {
                return $instance->$action($paramValues[0], $input ?? []);
            }
        }
        
        // Generic multi-parameter handling
        $paramValues = array_values($params);
        if (count($paramValues) >= $paramCount) {
            return $instance->$action(...array_slice($paramValues, 0, $paramCount));
        }
        
        // Mix of route params and input
        $args = array_merge($paramValues, [$input ?? []]);
        return $instance->$action(...array_slice($args, 0, $paramCount));
    }

    // Optional: Method to get route statistics for debugging
    public function getStats()
    {
        return [
            'total_routes' => array_sum(array_map('count', $this->routes)),
            'compiled_routes' => array_sum(array_map('count', $this->compiledRoutes)),
            'reflection_cache_size' => count($this->reflectionCache)
        ];
    }

    // Optional: Method to warm up the cache
    public function warmUpCache()
    {
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $path => $handler) {
                if (strpos($path, '{') !== false && !isset($this->compiledRoutes[$method])) {
                    $this->compileRoute($method, $path, $handler);
                }
            }
        }
    }
}