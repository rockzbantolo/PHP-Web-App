<?php
namespace Mws\Middleware;

use Mws\Helpers\Response;

class Auth
{
    private static string $validUser = 'Rockz';
    private static string $validPass = '122190';
    private static string $validApiKey = '1234567890abcdef';

    public static function InitBasicAuth(): void
    {
        // For Apache fallback if PHP_AUTH_USER is not set
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    [$user, $pass] = explode(':', base64_decode($matches[1]), 2);
                    $_SERVER['PHP_AUTH_USER'] = $user;
                    $_SERVER['PHP_AUTH_PW'] = $pass;
                }
            }
        }

        // Auth check
        if (
            !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_USER'] !== self::$validUser ||
            $_SERVER['PHP_AUTH_PW'] !== self::$validPass
        ) {
            header('WWW-Authenticate: Basic realm="MWS API"');
            Response::error("Authentication failed", 401);
            exit; // Make sure script stops after failed auth
        }
    }

    // API Key Authentication
    public static function InitApiKeyAuth(): void
    {
        $headers = getallheaders();
        $apiKey = $headers['MWS-API-KEY'] ?? null;

        if ($apiKey !== self::$validApiKey) {
            Response::error("Invalid API Key", 401);
            exit;
        }
    }
}
