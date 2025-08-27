<?php
// File: src/Models/User.php

namespace App\Models;

use PDO;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(int $limit = 30, ?int $cursor = null) {
        $query = "SELECT id, name, email, role FROM " . $this->table_name;
        if ($cursor !== null) {
            $query .= " WHERE id < :cursor";
        }
        $query .= " ORDER BY id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readAssistants(bool $includeBalance = false, int $limit = 30, ?int $cursor = null)
    {
        if ($includeBalance) {
            $query = "SELECT u.id, u.name, COALESCE(w.balance, 0) AS balance FROM {$this->table_name} u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.role = 'assistant'";
        } else {
            $query = "SELECT id, name FROM {$this->table_name} WHERE role = 'assistant'";
        }
        if ($cursor !== null) {
            $query .= " AND id < :cursor";
        }
        $query .= " ORDER BY id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, name, email, role FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name = :name, email = :email, password = :password, role = :role";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name = :name, email = :email, role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
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
