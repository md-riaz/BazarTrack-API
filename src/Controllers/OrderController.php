<?php
// File: src/Controllers/OrderController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Order;
use PDO;
use App\Core\RoleGuard;
use App\Core\LoggerTrait;

use App\Core\ResponseHelper;
use App\Core\Validator;

use App\Core\AuthMiddleware;


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
        if (!AuthMiddleware::check()) {
            return;
        }
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
                    ResponseHelper::error(400, 'Order ID required for PUT.');
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteOrder($id);
                } else {
                    ResponseHelper::error(400, 'Order ID required for DELETE.');
                }
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
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
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
            return;
        }
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
            ResponseHelper::error(404, 'Order not found.');
        }
    }

    private function createOrder() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['status'])) {
            ResponseHelper::error(400, 'status is required.');
            return;
        }

        if (
            (isset($data['assigned_to']) && $data['assigned_to'] !== null && !Validator::validateInt($data['assigned_to']))
        ) {
            ResponseHelper::error(400, 'Invalid input format.');
            return;
        }
        $guard = new RoleGuard();
        if (!$guard->checkRole(AuthMiddleware::$userId, ['owner'])) {
            ResponseHelper::error(403, 'Only owners can create orders.');
            return;
        }
        $this->order->created_by = AuthMiddleware::$userId;
        $this->order->assigned_to = $data['assigned_to'] ?? null;
        $this->order->status = $data['status'];
        $this->order->created_at = TIMESTAMP;
        $this->order->completed_at = null;
        if ($this->order->create()) {
            $this->logAction('order', $this->order->id, 'create', AuthMiddleware::$userId, $data);
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
            ResponseHelper::error(500, 'Unable to create order.');
        }
    }

    private function updateOrder($id) {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
            return;
        }
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['status'])) {
            ResponseHelper::error(400, 'status is required.');
            return;
        }

        if ((isset($data['assigned_to']) && $data['assigned_to'] !== null && !Validator::validateInt($data['assigned_to']))) {
            ResponseHelper::error(400, 'Invalid input format.');
            return;
        }

        $this->order->id = $id;
        $this->order->assigned_to = $data['assigned_to'] ?? null;
        $this->order->status = $data['status'];
        $this->order->completed_at = $data['status'] === 'completed' ? TIMESTAMP : null;
        if ($this->order->update()) {
            echo json_encode([
                'id' => $this->order->id,
                'assigned_to' => $this->order->assigned_to,
                'status' => $this->order->status,
                'completed_at' => $this->order->completed_at,
            ]);
        } else {
            ResponseHelper::error(500, 'Unable to update order.');
        }
    }

    private function deleteOrder($id) {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
            return;
        }
        $this->order->id = $id;
        if ($this->order->delete()) {
            echo json_encode(["message" => "Order deleted."]);
        } else {
            ResponseHelper::error(500, 'Unable to delete order.');
        }
    }

    public function assignOrder($id)
    {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
}
        if (!AuthMiddleware::check()) {
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['user_id'])) {
            ResponseHelper::error(400, 'user_id is required.');
            return;
        }

        if (!Validator::validateInt($data['user_id'])) {
            ResponseHelper::error(400, 'Invalid user_id.');
            return;
        }

        $guard = new RoleGuard();
        $isSelf = (int)$data['user_id'] === AuthMiddleware::$userId;
        if ($isSelf && !$guard->checkRole(AuthMiddleware::$userId, ['assistant'])) {
            ResponseHelper::error(403, 'Only assistants can self-assign.');
            return;
        }
        if (!$isSelf && !$guard->checkRole(AuthMiddleware::$userId, ['owner'])) {
            ResponseHelper::error(403, 'Only owners can assign others.');
            return;
        }

        $this->order->id = $id;
        $this->order->assigned_to = $data['user_id'];
        $this->order->status = 'assigned';
        if ($this->order->update()) {
            $this->logAction('order', $id, 'assign', AuthMiddleware::$userId, $data);
            echo json_encode(["message" => "Order assigned."]);
        } else {
            ResponseHelper::error(500, 'Unable to assign order.');
        }
    }

    public function completeOrder($id)
    {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
}
        if (!AuthMiddleware::check()) {
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $guard = new RoleGuard();
        if (!$guard->checkRole(AuthMiddleware::$userId, ['assistant'])) {
            ResponseHelper::error(403, 'Only assistants can complete orders.');
            return;
        }
        $this->order->id = $id;
        $this->order->assigned_to = AuthMiddleware::$userId;
        $this->order->status = 'completed';
        $this->order->completed_at = TIMESTAMP;
        if ($this->order->update()) {
            $this->logAction('order', $id, 'complete', AuthMiddleware::$userId, $data);
            echo json_encode(["message" => "Order marked as completed."]);
        } else {
            ResponseHelper::error(500, 'Unable to complete order.');
        }
    }
}
