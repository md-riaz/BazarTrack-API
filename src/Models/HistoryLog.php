<?php
// File: src/Models/HistoryLog.php

namespace App\Models;

use PDO;
use Exception;

class HistoryLog {
    private $conn;
    private $table_name = "history_logs";

    public $id;
    public $entity_type;
    public $entity_id;
    public $action;
    public $changed_by_user_id;
    public $changed_by_user_name;
    public $timestamp;
    public $data_snapshot;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(int $limit = 30, ?int $cursor = null) {
        $query = "SELECT h.id, h.entity_type, h.entity_id, h.action, h.changed_by_user_id, u.name AS changed_by_user_name, h.timestamp, h.data_snapshot FROM " . $this->table_name . " h LEFT JOIN users u ON h.changed_by_user_id = u.id";
        if ($cursor !== null) {
            $query .= " WHERE h.id < :cursor";
        }
        $query .= " ORDER BY h.id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readByUser($userId, int $limit = 30, ?int $cursor = null) {
        $query = "SELECT h.id, h.entity_type, h.entity_id, h.action, h.changed_by_user_id, u.name AS changed_by_user_name, h.timestamp, h.data_snapshot FROM " . $this->table_name . " h LEFT JOIN users u ON h.changed_by_user_id = u.id WHERE h.changed_by_user_id = :user_id";
        if ($cursor !== null) {
            $query .= " AND h.id < :cursor";
        }
        $query .= " ORDER BY h.id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readByEntityType(int $limit = 30, ?int $cursor = null, ?int $userId = null) {
        $query = "SELECT h.id, h.entity_type, h.entity_id, h.action, h.changed_by_user_id, u.name AS changed_by_user_name, h.timestamp, h.data_snapshot FROM " . $this->table_name . " h LEFT JOIN users u ON h.changed_by_user_id = u.id WHERE h.entity_type = :entity_type";
        if ($userId !== null) {
            $query .= " AND h.changed_by_user_id = :user_id";
        }
        if ($cursor !== null) {
            $query .= " AND h.id < :cursor";
        }
        $query .= " ORDER BY h.id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':entity_type', $this->entity_type, PDO::PARAM_STR);
        if ($userId !== null) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readByEntity(int $limit = 30, ?int $cursor = null, ?int $userId = null) {
        $query = "SELECT h.id, h.entity_type, h.entity_id, h.action, h.changed_by_user_id, u.name AS changed_by_user_name, h.timestamp, h.data_snapshot FROM " . $this->table_name . " h LEFT JOIN users u ON h.changed_by_user_id = u.id WHERE h.entity_type = :entity_type AND h.entity_id = :entity_id";
        if ($userId !== null) {
            $query .= " AND h.changed_by_user_id = :user_id";
        }
        if ($cursor !== null) {
            $query .= " AND h.id < :cursor";
        }
        $query .= " ORDER BY h.id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':entity_type', $this->entity_type, PDO::PARAM_STR);
        $stmt->bindParam(':entity_id', $this->entity_id, PDO::PARAM_INT);
        if ($userId !== null) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        if ($cursor !== null) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET entity_type = :entity_type, entity_id = :entity_id, action = :action, changed_by_user_id = :changed_by_user_id, timestamp = :timestamp, data_snapshot = :data_snapshot";
        $stmt = $this->conn->prepare($query);
        $this->entity_type = htmlspecialchars(strip_tags($this->entity_type));
        $this->entity_id = htmlspecialchars(strip_tags($this->entity_id));
        $this->action = htmlspecialchars(strip_tags($this->action));
        $this->changed_by_user_id = htmlspecialchars(strip_tags($this->changed_by_user_id));
        $this->timestamp = htmlspecialchars(strip_tags($this->timestamp));
        $json_snapshot = json_encode($this->data_snapshot);
        $stmt->bindParam(':entity_type', $this->entity_type);
        $stmt->bindParam(':entity_id', $this->entity_id);
        $stmt->bindParam(':action', $this->action);
        $stmt->bindParam(':changed_by_user_id', $this->changed_by_user_id);
        $stmt->bindParam(':timestamp', $this->timestamp);
        $stmt->bindParam(':data_snapshot', $json_snapshot);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
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
