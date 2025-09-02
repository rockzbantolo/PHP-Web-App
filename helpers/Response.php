<?php
namespace Mws\Helpers;

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

    public static function success(array $data = [], string $message = "OK", int $code = 200)
    {
        http_response_code($code);

        $response = [
            'code'         => $code,
            'status'       => 'Success',
            'message'      => $message,
            'timestamp'    => date('c'),
            //'path'         => $_SERVER['REQUEST_URI'] ?? null,
            //'method'       => $_SERVER['REQUEST_METHOD'] ?? null,
            'duration_ms'  => round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2),
            //'memory_usage' => self::formatBytes(memory_get_usage(true)),
            //'request_id'   => bin2hex(random_bytes(8)),
            //'client_ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
            //'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'data'         => $data
        ];

        self::send($response);
    }

    public static function error(string $message = "An error occurred", int $code = 500, $exception = null)
    {
        http_response_code($code);

        $response = [
            'code' => $code,
            'status' => 'Error',
            'message' => $message
        ];

        if (self::isDebugMode()) {
            if ($exception instanceof \Throwable) {
                $response['debug'] = [
                    'code' => $exception->getCode(),
                    'type' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'memory_usage' => self::formatBytes(memory_get_usage(true)),
                    'trace' => array_slice(explode("\n", $exception->getTraceAsString()), 0, 10)
                ];
            } elseif (is_string($exception)) {
                $response['debug'] = ['message' => $exception];
            }
        }

        self::send($response);
    }

    public static function validationError(array $errors, string $message = "Validation failed", int $code = 422)
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
        self::error($message, 404);
    }

    public static function unauthorized(string $message = "Unauthorized access")
    {
        self::error($message, 401);
    }

    private static function send(array $response)
    {
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

     public static function normalsuccess($message, $status = 200) {
        http_response_code($status);
        echo json_encode(['status' => 'success', 'message' => $message]);
        exit;
    }
}
