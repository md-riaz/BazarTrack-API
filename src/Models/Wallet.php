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
}
