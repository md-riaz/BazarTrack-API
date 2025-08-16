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
    public $timestamp;
    public $data_snapshot;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, entity_type, entity_id, action, changed_by_user_id, timestamp, data_snapshot FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByUser($userId) {
        $query = "SELECT id, entity_type, entity_id, action, changed_by_user_id, timestamp, data_snapshot FROM " . $this->table_name . " WHERE changed_by_user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readByEntityType() {
        $query = "SELECT id, entity_type, entity_id, action, changed_by_user_id, timestamp, data_snapshot FROM " . $this->table_name . " WHERE entity_type = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->entity_type, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt;
    }

    public function readByEntity() {
        $query = "SELECT id, entity_type, entity_id, action, changed_by_user_id, timestamp, data_snapshot FROM " . $this->table_name . " WHERE entity_type = ? AND entity_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->entity_type, PDO::PARAM_STR);
        $stmt->bindParam(2, $this->entity_id, PDO::PARAM_INT);
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
