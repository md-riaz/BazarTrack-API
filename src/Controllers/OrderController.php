<?php
// File: src/Controllers/OrderController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Order;
use PDO;
use App\Core\RoleGuard;
use App\Core\LoggerTrait;

class OrderController {
    use LoggerTrait;
    private $db;
    private $order;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
    }

    private function getUserRole($userId) {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['role'];
        }
        return null;
    }

    public function processRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getOrder($id);
                } else {
                    $this->getOrders();
                }
                break;
            case 'POST':
                $this->createOrder();
                break;
            case 'PUT':
                if ($id) {
                    $this->updateOrder($id);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Order ID required for PUT."]);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteOrder($id);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Order ID required for DELETE."]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }

    private function getOrders() {
        $stmt = $this->order->readAll();
        $orders_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders_arr[] = [
                'id' => $row['id'],
                'created_by' => $row['created_by'],
                'assigned_to' => $row['assigned_to'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'completed_at' => $row['completed_at'],
            ];
        }
        echo json_encode($orders_arr);
    }

    private function getOrder($id) {
        $this->order->id = $id;
        $stmt = $this->order->readOne();
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'id' => $row['id'],
                'created_by' => $row['created_by'],
                'assigned_to' => $row['assigned_to'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'completed_at' => $row['completed_at'],
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Order not found."]);
        }
    }

    private function createOrder() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['created_by']) || empty($data['status']) || empty($data['created_at'])) {
            http_response_code(400);
            echo json_encode(["message" => "created_by, status, and created_at are required."]);
            return;
        }
        $guard = new RoleGuard();
        if (!$guard->checkRole($data['created_by'], ['owner'])) {
            http_response_code(403);
            echo json_encode(["message" => "Only owners can create orders."]);
            return;
        }
        $this->order->created_by = $data['created_by'];
        $this->order->assigned_to = $data['assigned_to'] ?? null;
        $this->order->status = $data['status'];
        $this->order->created_at = $data['created_at'];
        $this->order->completed_at = $data['completed_at'] ?? null;
        if ($this->order->create()) {
            $this->logAction('order', $this->order->id, 'create', (int)$data['created_by'], $data);
            http_response_code(201);
            echo json_encode([
                'id' => $this->order->id,
                'created_by' => $this->order->created_by,
                'assigned_to' => $this->order->assigned_to,
                'status' => $this->order->status,
                'created_at' => $this->order->created_at,
                'completed_at' => $this->order->completed_at,
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to create order."]);
        }
    }

    private function updateOrder($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['status'])) {
            http_response_code(400);
            echo json_encode(["message" => "status is required."]);
            return;
        }
        $this->order->id = $id;
        $this->order->assigned_to = $data['assigned_to'] ?? null;
        $this->order->status = $data['status'];
        $this->order->completed_at = $data['completed_at'] ?? null;
        if ($this->order->update()) {
            echo json_encode([
                'id' => $this->order->id,
                'assigned_to' => $this->order->assigned_to,
                'status' => $this->order->status,
                'completed_at' => $this->order->completed_at,
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update order."]);
        }
    }

    private function deleteOrder($id) {
        $this->order->id = $id;
        if ($this->order->delete()) {
            echo json_encode(["message" => "Order deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete order."]);
        }
    }

    public function assignOrder($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['user_id']) || empty($data['assigned_by'])) {
            http_response_code(400);
            echo json_encode(["message" => "user_id and assigned_by are required."]);
            return;
        }

        $guard = new RoleGuard();
        $isSelf = (int)$data['user_id'] === (int)$data['assigned_by'];
        if ($isSelf && !$guard->checkRole($data['assigned_by'], ['assistant'])) {
            http_response_code(403);
            echo json_encode(["message" => "Only assistants can self-assign."]);
            return;
        }
        if (!$isSelf && !$guard->checkRole($data['assigned_by'], ['owner'])) {
            http_response_code(403);
            echo json_encode(["message" => "Only owners can assign others."]);
            return;
        }

        $this->order->id = $id;
        $this->order->assigned_to = $data['user_id'];
        $this->order->status = 'assigned';
        if ($this->order->update()) {
            $this->logAction('order', $id, 'assign', (int)$data['assigned_by'], $data);
            echo json_encode(["message" => "Order assigned."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to assign order."]);
        }
    }

    public function completeOrder($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['user_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "user_id is required."]);
            return;
        }
        $guard = new RoleGuard();
        if (!$guard->checkRole($data['user_id'], ['assistant'])) {
            http_response_code(403);
            echo json_encode(["message" => "Only assistants can complete orders."]);
            return;
        }
        $this->order->id = $id;
        $this->order->assigned_to = $data['user_id'];
        $this->order->status = 'completed';
        $this->order->completed_at = $data['completed_at'] ?? null;
        if ($this->order->update()) {
            $this->logAction('order', $id, 'complete', (int)$data['user_id'], $data);
            echo json_encode(["message" => "Order marked as completed."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to complete order."]);
        }
    }
}
