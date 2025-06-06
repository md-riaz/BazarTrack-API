<?php
namespace App\Models;

use PDO;

class Token
{
    private PDO $conn;
    private string $table_name = 'tokens';

    public int $id;
    public int $user_id;
    public string $token;
    public string $created_at;
    public ?string $revoked_at;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function create(): bool
    {
        $query = 'INSERT INTO ' . $this->table_name . ' SET user_id = :user_id, token = :token, created_at = :created_at';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':token', $this->token);
        $stmt->bindParam(':created_at', $this->created_at);
        if ($stmt->execute()) {
            $this->id = (int)$this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function revoke(string $token): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM ' . $this->table_name . ' WHERE token = ?');
        $stmt->bindParam(1, $token);
        return $stmt->execute();
    }

    public function findUserId(string $token): ?int
    {
        $stmt = $this->conn->prepare('SELECT user_id FROM ' . $this->table_name . ' WHERE token = ? LIMIT 1');
        $stmt->bindParam(1, $token);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return (int)$row['user_id'];
        }
        return null;
    }
}
