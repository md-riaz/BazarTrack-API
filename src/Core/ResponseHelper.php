<?php
namespace App\Core;

class ResponseHelper
{
    public static function error(int $status, string $message, ?string $errorCode = null): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }
        echo json_encode([
            'error' => $errorCode ?? (string)$status,
            'msg' => $message,
            'data' => null
        ]);
    }

    public static function success(string $message, $data = null, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }
        echo json_encode([
            'error' => null,
            'msg' => $message,
            'data' => $data
        ]);
    }
}
