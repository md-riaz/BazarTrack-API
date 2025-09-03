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
        $query = "SELECT p.id, p.user_id, u.name AS assistant_name, p.owner_id, o.name AS owner_name, p.amount, p.type, p.created_at FROM " . $this->table_name . " p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN users o ON p.owner_id = o.id";
        $conditions = [];
        $params = [];
        if (isset($filters['user_id'])) {
            $conditions[] = "p.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (isset($filters['owner_id'])) {
            $conditions[] = "p.owner_id = :owner_id";
            $params[':owner_id'] = $filters['owner_id'];
        }
        if (isset($filters['type'])) {
            $conditions[] = "p.type = :type";
            $params[':type'] = $filters['type'];
        }
        if (isset($filters['from'])) {
            $conditions[] = "p.created_at >= :from";
            $params[':from'] = $filters['from'];
        }
        if (isset($filters['to'])) {
            $conditions[] = "p.created_at <= :to";
            $params[':to'] = $filters['to'];
        }
        if ($cursor !== null) {
            $conditions[] = "p.id < :cursor";
            $params[':cursor'] = $cursor;
        }
        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY p.id DESC LIMIT :limit";
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
