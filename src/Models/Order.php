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

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, created_by, assigned_to, status, created_at, completed_at FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, created_by, assigned_to, status, created_at, completed_at FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET created_by = :created_by, assigned_to = :assigned_to, status = :status, created_at = :created_at";
        $stmt = $this->conn->prepare($query);
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        $this->assigned_to = htmlspecialchars(strip_tags($this->assigned_to));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->created_at = htmlspecialchars(strip_tags($this->created_at));
        $stmt->bindParam(':created_by', $this->created_by);
        $stmt->bindParam(':assigned_to', $this->assigned_to);
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
        $this->assigned_to = htmlspecialchars(strip_tags($this->assigned_to));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->completed_at = htmlspecialchars(strip_tags($this->completed_at));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':assigned_to', $this->assigned_to);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':completed_at', $this->completed_at);
        $stmt->bindParam(':id', $this->id);
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
