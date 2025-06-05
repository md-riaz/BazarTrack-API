<?php
// File: src/Controllers/OrderItemController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\OrderItem;
use PDO;

class OrderItemController {
    private $db;
    private $item;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->item = new OrderItem($this->db);
    }

    public function processRequest($method, $id = null, $parentId = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getItem($id);
                } elseif ($parentId) {
                    $this->getItemsByOrder($parentId);
                } else {
                    $this->getItems();
                }
                break;
            case 'POST':
                $this->createItem();
                break;
            case 'PUT':
                if ($id) {
                    $this->updateItem($id);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Item ID required for PUT."]);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteItem($id);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Item ID required for DELETE."]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }

    private function getItems() {
        $stmt = $this->item->readAll();
        $items_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items_arr[] = [
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'estimated_cost' => $row['estimated_cost'],
                'actual_cost' => $row['actual_cost'],
                'status' => $row['status'],
            ];
        }
        echo json_encode($items_arr);
    }

    private function getItemsByOrder($orderId) {
        $this->item->order_id = $orderId;
        $stmt = $this->item->readByOrder();
        $items_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items_arr[] = [
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'estimated_cost' => $row['estimated_cost'],
                'actual_cost' => $row['actual_cost'],
                'status' => $row['status'],
            ];
        }
        echo json_encode($items_arr);
    }

    private function getItem($id) {
        $this->item->id = $id;
        $stmt = $this->item->readOne();
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'estimated_cost' => $row['estimated_cost'],
                'actual_cost' => $row['actual_cost'],
                'status' => $row['status'],
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Item not found."]);
        }
    }

    private function createItem() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['order_id']) || empty($data['product_name']) || empty($data['quantity']) || empty($data['status'])) {
            http_response_code(400);
            echo json_encode(["message" => "order_id, product_name, quantity, and status are required."]);
            return;
        }
        $this->item->order_id = $data['order_id'];
        $this->item->product_name = $data['product_name'];
        $this->item->quantity = $data['quantity'];
        $this->item->unit = $data['unit'] ?? '';
        $this->item->estimated_cost = $data['estimated_cost'] ?? null;
        $this->item->actual_cost = $data['actual_cost'] ?? null;
        $this->item->status = $data['status'];
        if ($this->item->create()) {
            http_response_code(201);
            echo json_encode([
                'id' => $this->item->id,
                'order_id' => $this->item->order_id,
                'product_name' => $this->item->product_name,
                'quantity' => $this->item->quantity,
                'unit' => $this->item->unit,
                'estimated_cost' => $this->item->estimated_cost,
                'actual_cost' => $this->item->actual_cost,
                'status' => $this->item->status,
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to create item."]);
        }
    }

    private function updateItem($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['product_name']) || empty($data['quantity']) || empty($data['status'])) {
            http_response_code(400);
            echo json_encode(["message" => "product_name, quantity, and status are required."]);
            return;
        }
        $this->item->id = $id;
        $this->item->product_name = $data['product_name'];
        $this->item->quantity = $data['quantity'];
        $this->item->unit = $data['unit'] ?? '';
        $this->item->estimated_cost = $data['estimated_cost'] ?? null;
        $this->item->actual_cost = $data['actual_cost'] ?? null;
        $this->item->status = $data['status'];
        if ($this->item->update()) {
            echo json_encode([
                'id' => $this->item->id,
                'order_id' => $this->item->order_id,
                'product_name' => $this->item->product_name,
                'quantity' => $this->item->quantity,
                'unit' => $this->item->unit,
                'estimated_cost' => $this->item->estimated_cost,
                'actual_cost' => $this->item->actual_cost,
                'status' => $this->item->status,
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update item."]);
        }
    }

    private function deleteItem($id) {
        $this->item->id = $id;
        if ($this->item->delete()) {
            echo json_encode(["message" => "Item deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete item."]);
        }
    }
}
