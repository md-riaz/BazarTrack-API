<?php
namespace App\Core;

use PDO;

class AuthMiddleware
{
    public static function check(): bool
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return false;
        }
        if (!preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return false;
        }
        $token = $m[1];
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT user_id FROM tokens WHERE token = ? LIMIT 1');
        $stmt->bindParam(1, $token);
        $stmt->execute();
        if ($stmt->rowCount() === 1) {
            return true;
        }
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        return false;
    }
}
