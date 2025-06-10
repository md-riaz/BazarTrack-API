<?php
namespace App\Core;

use PDO;

class AuthMiddleware
{
    public static ?int $userId = null;
    public static ?string $role = null;

    public static function check(): bool
    {
        if (self::$userId !== null) {
            return true;
        }

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
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            self::$userId = (int)$row['user_id'];
            $roleStmt = $db->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
            $roleStmt->bindParam(1, self::$userId);
            $roleStmt->execute();
            self::$role = $roleStmt->fetch(PDO::FETCH_COLUMN) ?: null;
            return true;
        }
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        return false;
    }
}
