<?php
namespace App\Core;

class ResponseHelper
{
    public static function error(int $status, string $message): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
    }
}
