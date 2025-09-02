<?php
namespace Mws\Helpers;

use Exception;

class Env
{
    /**
     * Load environment variables from a .env file.
     *
     * @param string $filePath Path to the .env file
     * @throws Exception If the file does not exist
     */
    public static function InitEnv(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at $filePath");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            // Split into key/value
            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\""); // Remove quotes

            // Set environment variable
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}
