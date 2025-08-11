<?php
// File: src/Models/OrderItem.php

namespace App\Models;

use PDO;
use Exception;

class OrderItem {
    private $conn;
    private $table_name = "order_items";

    public $id;
    public $order_id;
    public $product_name;
    public $quantity;
    public $unit;
    public $estimated_cost;
    public $actual_cost;
    public $status;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, order_id, product_name, quantity, unit, estimated_cost, actual_cost, status
                  FROM {$this->table_name}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, order_id, product_name, quantity, unit, estimated_cost, actual_cost, status
                  FROM {$this->table_name}
                  WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function readByOrder() {
        $query = "SELECT id, order_id, product_name, quantity, unit, estimated_cost, actual_cost, status
                  FROM {$this->table_name}
                  WHERE order_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->order_id);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO {$this->table_name}
                  SET order_id       = :order_id,
                      product_name   = :product_name,
                      quantity       = :quantity,
                      unit           = :unit,
                      estimated_cost = :estimated_cost,
                      actual_cost    = :actual_cost,
                      status         = :status";
        $stmt = $this->conn->prepare($query);

        // sanitize mandatory fields
        $this->order_id     = htmlspecialchars(strip_tags($this->order_id));
        $this->product_name = htmlspecialchars(strip_tags($this->product_name));
        $this->quantity     = htmlspecialchars(strip_tags($this->quantity));
        $this->unit         = htmlspecialchars(strip_tags($this->unit));
        $this->status       = htmlspecialchars(strip_tags($this->status));

        // --- Handle estimated_cost safely ---
        if (isset($this->estimated_cost) && $this->estimated_cost !== '') {
            $raw = (string)$this->estimated_cost;
            $this->estimated_cost = htmlspecialchars(strip_tags($raw));
        } else {
            $this->estimated_cost = null;
        }

        // --- Handle actual_cost safely ---
        if (isset($this->actual_cost) && $this->actual_cost !== '') {
            $raw = (string)$this->actual_cost;
            $this->actual_cost = htmlspecialchars(strip_tags($raw));
        } else {
            $this->actual_cost = null;
        }

        // bind parameters
        $stmt->bindParam(':order_id',      $this->order_id);
        $stmt->bindParam(':product_name',  $this->product_name);
        $stmt->bindParam(':quantity',      $this->quantity);
        $stmt->bindParam(':unit',          $this->unit);

        if ($this->estimated_cost === null) {
            $stmt->bindValue(':estimated_cost', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':estimated_cost', $this->estimated_cost);
        }

        if ($this->actual_cost === null) {
            $stmt->bindValue(':actual_cost', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':actual_cost', $this->actual_cost);
        }

        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE {$this->table_name}
                  SET product_name   = :product_name,
                      quantity       = :quantity,
                      unit           = :unit,
                      estimated_cost = :estimated_cost,
                      actual_cost    = :actual_cost,
                      status         = :status
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // sanitize mandatory fields
        $this->product_name = htmlspecialchars(strip_tags($this->product_name));
        $this->quantity     = htmlspecialchars(strip_tags($this->quantity));
        $this->unit         = htmlspecialchars(strip_tags($this->unit));
        $this->status       = htmlspecialchars(strip_tags($this->status));
        $this->id           = htmlspecialchars(strip_tags($this->id));

        // --- Handle estimated_cost safely ---
        if (isset($this->estimated_cost) && $this->estimated_cost !== '') {
            $raw = (string)$this->estimated_cost;
            $this->estimated_cost = htmlspecialchars(strip_tags($raw));
        } else {
            $this->estimated_cost = null;
        }

        // --- Handle actual_cost safely ---
        if (isset($this->actual_cost) && $this->actual_cost !== '') {
            $raw = (string)$this->actual_cost;
            $this->actual_cost = htmlspecialchars(strip_tags($raw));
        } else {
            $this->actual_cost = null;
        }

        // bind parameters
        $stmt->bindParam(':product_name', $this->product_name);
        $stmt->bindParam(':quantity',     $this->quantity);
        $stmt->bindParam(':unit',         $this->unit);

        if ($this->estimated_cost === null) {
            $stmt->bindValue(':estimated_cost', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':estimated_cost', $this->estimated_cost);
        }

        if ($this->actual_cost === null) {
            $stmt->bindValue(':actual_cost', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':actual_cost', $this->actual_cost);
        }

        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id',     $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM {$this->table_name} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
