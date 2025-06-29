<?php
// File: src/Models/Wallet.php

namespace App\Models;

use PDO;

class Wallet {
    private $conn;
    private $table_name = "wallets";

    public $user_id;
    public $balance;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getBalance($user_id) {
        $query = "SELECT balance FROM " . $this->table_name . " WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function readTransactions($user_id) {
        $query = "SELECT id, user_id, amount, type, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function updateBalance(int $userId, float $amount): bool
    {
        $query = "UPDATE {$this->table_name} SET balance = balance + :amount WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function addTransaction(int $userId, float $amount, string $type, string $createdAt): bool
    {
        $query = "INSERT INTO transactions (user_id, amount, type, created_at) VALUES (:user_id, :amount, :type, :created_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':created_at', $createdAt);
        return $stmt->execute();
    }
}
