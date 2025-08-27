<?php
// File: src/Models/Payment.php

namespace App\Models;

use PDO;

class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $user_id;
    public $owner_id;
    public $amount;
    public $type; // e.g., "credit", "debit"
    public $created_at;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(array $filters = [], int $limit = 30, ?int $cursor = null) {
        $query = "SELECT id, user_id, owner_id, amount, type, created_at FROM " . $this->table_name;
        $conditions = [];
        $params = [];
        if (isset($filters['user_id'])) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (isset($filters['owner_id'])) {
            $conditions[] = "owner_id = :owner_id";
            $params[':owner_id'] = $filters['owner_id'];
        }
        if (isset($filters['type'])) {
            $conditions[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        if (isset($filters['from'])) {
            $conditions[] = "created_at >= :from";
            $params[':from'] = $filters['from'];
        }
        if (isset($filters['to'])) {
            $conditions[] = "created_at <= :to";
            $params[':to'] = $filters['to'];
        }
        if ($cursor !== null) {
            $conditions[] = "id < :cursor";
            $params[':cursor'] = $cursor;
        }
        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id = :user_id, owner_id = :owner_id, amount = :amount, type = :type, created_at = :created_at";
        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->created_at = htmlspecialchars(strip_tags($this->created_at));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':owner_id', $this->owner_id);
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
