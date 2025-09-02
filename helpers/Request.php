<?php
namespace Mws\Helpers;

use Mws\Core\Https;

class Request
{
    /**
     * Initialize and return the router and cleaned path
     *
     * @return array [$router, $method, $path]
     */
    public static function initRouter(): array
    {
        $router = new Https();
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Clean URI: remove base path and normalize
        $basePath = dirname(parse_url(getenv('BASE_URL'), PHP_URL_PATH));
        $path     = '/' . ltrim(preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri), '/');

        // Debug mode (enable only when needed)
        /*if (getenv('APP_DEBUG') === 'true') {
            echo "<pre>";
            echo "Method: " . $method . PHP_EOL;
            echo "URI: " . $uri . PHP_EOL;
            echo "Base Path: " . $basePath . PHP_EOL;
            echo "Final Path: " . $path . PHP_EOL;
            echo "</pre>";
            exit;
        }*/

        return [$router, $method, $path];
    }
}
