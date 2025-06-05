<?php
namespace App\Core;

use App\Core\Database;
use PDO;

class RoleGuard
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function checkRole(int $userId, array $allowedRoles): bool
    {
        $stmt = $this->db->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return in_array($row['role'], $allowedRoles, true);
        }
        return false;
    }
}
