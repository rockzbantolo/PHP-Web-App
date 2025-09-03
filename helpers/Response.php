<?php
namespace Mws\Helpers;

use Mws\Core\StatusCode;

class Response
{
     private static function isDebugMode(): bool
    {
        return getenv('APP_DEBUG') === 'true';
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function success(
        array $data = [],
        string $message = "OK",
        int $code = StatusCode::OK,
        array $pagination = null
    )
    {
        http_response_code($code);

        $response = [
            'code'        => $code,
            'status'      => 'Success',
            'message'     => $message,
            'timestamp'   => date('c'),
            'duration_ms' => round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2),
            'data'        => $data
        ];

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        self::send($response);
    }

    public static function error(
        string $message = "An error occurred",
        int $code = StatusCode::INTERNAL_SERVER_ERROR, 
        $exception = null
    )
    {
        http_response_code($code);

        $response = [
            'code'    => $code,
            'status'  => 'Error',
            'message' => $message,
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri'    => $_SERVER['REQUEST_URI'] ?? null,
            ]
        ];

        if (self::isDebugMode()) {
            if ($exception instanceof \Throwable) {
                // Prepare trace with single slashes and function info
                $trace = [];
                foreach ($exception->getTrace() as $t) {
                    $trace[] = [
                        'file'     => isset($t['file']) ? str_replace('\\', '/', $t['file']) : null,
                        'line'     => $t['line'] ?? null,
                        'function' => $t['function'] ?? null,
                        'class'    => $t['class'] ?? null,
                    ];
                }

                $response['debug'] = [
                    'code'         => $exception->getCode(),
                    'type'         => get_class($exception),
                    'file'         => str_replace('\\', '/', $exception->getFile()),
                    'line'         => $exception->getLine(),
                    'memory_usage' => self::formatBytes(memory_get_usage(true)),
                    'trace'        => array_slice($trace, 0, 10)
                ];
            } elseif (is_string($exception)) {
                $response['debug'] = ['message' => $exception];
            }
        }

        self::send($response);
    }

    public static function validationError(
        array $errors,
        string $message = "Validation failed", 
        int $code = StatusCode::UNPROCESSABLE_ENTITY)
    {
        http_response_code($code);
        self::send([
            'code' => $code,
            'status' => 'Error',
            'message' => $message,
            'errors' => $errors
        ]);
    }

    public static function notFound(string $message = "Resource not found")
    {
        self::error($message, StatusCode::NOT_FOUND);
    }

    public static function unauthorized(string $message = "Unauthorized access")
    {
        self::error($message, StatusCode::UNAUTHORIZED);
    }

    private static function send(array $response)
    {
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

     public static function normalsuccess($message, $status = StatusCode::OK) {
        http_response_code($status);
        echo json_encode(['status' => 'success', 'message' => $message]);
        exit;
    }
}
