<?php
// File: src/Models/Payment.php

namespace App\Models;

use PDO;

class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $user_id;
    public $amount;
    public $type; // e.g., "credit", "debit"
    public $created_at;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, user_id, amount, type, created_at FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id = :user_id, amount = :amount, type = :type, created_at = :created_at";
        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->created_at = htmlspecialchars(strip_tags($this->created_at));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':created_at', $this->created_at);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
