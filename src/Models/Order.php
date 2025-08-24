<?php
// File: src/Models/Order.php

namespace App\Models;

use PDO;
use Exception;

class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $created_by;
    public $assigned_to;
    public $status;
    public $created_at;
    public $completed_at;
    public $assigned_user_name;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(array $filters = [], int $limit = 30, ?int $cursor = null) {
        $query = "SELECT o.id, o.created_by, o.assigned_to, u.name AS assigned_user_name, o.status, o.created_at, o.completed_at FROM " . $this->table_name . " o LEFT JOIN users u ON o.assigned_to = u.id";
        $conditions = [];
        $params = [];

        if (isset($filters['status'])) {
            $conditions[] = "o.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (array_key_exists('assigned_to', $filters)) {
            if ($filters['assigned_to'] === null) {
                $conditions[] = "o.assigned_to IS NULL";
            } else {
                $conditions[] = "o.assigned_to = :assigned_to";
                $params[':assigned_to'] = $filters['assigned_to'];
            }
        }
        if ($cursor !== null) {
            $conditions[] = "o.id < :cursor";
            $params[':cursor'] = $cursor;
        }
        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY o.id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT o.id, o.created_by, o.assigned_to, u.name AS assigned_user_name, o.status, o.created_at, o.completed_at FROM " . $this->table_name . " o LEFT JOIN users u ON o.assigned_to = u.id WHERE o.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET created_by = :created_by, assigned_to = :assigned_to, status = :status, created_at = :created_at";
        $stmt = $this->conn->prepare($query);
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->created_at = htmlspecialchars(strip_tags($this->created_at));
        $stmt->bindValue(':created_by', $this->created_by, PDO::PARAM_INT);
        if ($this->assigned_to !== null) {
            $this->assigned_to = htmlspecialchars(strip_tags($this->assigned_to));
            $stmt->bindValue(':assigned_to', $this->assigned_to, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':assigned_to', null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':created_at', $this->created_at);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET assigned_to = :assigned_to, status = :status, completed_at = :completed_at WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        if ($this->assigned_to !== null) {
            $this->assigned_to = htmlspecialchars(strip_tags($this->assigned_to));
            $stmt->bindValue(':assigned_to', $this->assigned_to, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':assigned_to', null, PDO::PARAM_NULL);
        }
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':status', $this->status);
        if ($this->completed_at !== null) {
            $this->completed_at = htmlspecialchars(strip_tags($this->completed_at));
            $stmt->bindValue(':completed_at', $this->completed_at);
        } else {
            $stmt->bindValue(':completed_at', null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
