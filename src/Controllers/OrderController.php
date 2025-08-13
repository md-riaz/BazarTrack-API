<?php
// File: src/Controllers/OrderController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Order;
use App\Models\OrderItem;
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
        ResponseHelper::success('Orders retrieved successfully', $orders_arr);
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
            ResponseHelper::success('Order retrieved successfully', [
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
        if (isset($data['assigned_to']) && $data['assigned_to'] !== null) {
            $assigneeRole = $this->getUserRole((int)$data['assigned_to']);
            if ($assigneeRole !== 'assistant') {
                ResponseHelper::error(400, 'assigned_to must be an assistant.');
                return;
            }
        }
        $guard = new RoleGuard();
        if (!$guard->checkRole(AuthMiddleware::$userId, ['owner'])) {
            ResponseHelper::error(403, 'Only owners can create orders.');
            return;
        }

        $items = $data['items'] ?? [];

        $this->order->created_by = AuthMiddleware::$userId;
        $this->order->assigned_to = $data['assigned_to'] ?? null;
        $this->order->status = $data['status'];
        $this->order->created_at = TIMESTAMP;
        $this->order->completed_at = null;

        $createdItems = [];
        $this->db->beginTransaction();
        try {
            if (!$this->order->create()) {
                throw new \Exception('Unable to create order.', 500);
            }

            if (is_array($items)) {
                $itemModel = new OrderItem($this->db);
                foreach ($items as $item) {
                    foreach (['product_name', 'quantity', 'status'] as $field) {
                        if (empty($item[$field])) {
                            throw new \Exception("$field is required for each item.", 400);
                        }
                    }
                    if (!Validator::validateFloat($item['quantity'])) {
                        throw new \Exception('Invalid item quantity.', 400);
                    }
                    if ((isset($item['estimated_cost']) && $item['estimated_cost'] !== null && !Validator::validateFloat($item['estimated_cost'])) ||
                        (isset($item['actual_cost']) && $item['actual_cost'] !== null && !Validator::validateFloat($item['actual_cost']))) {
                        throw new \Exception('Invalid item cost format.', 400);
                    }

                    $itemModel->order_id = $this->order->id;
                    $itemModel->product_name = $item['product_name'];
                    $itemModel->quantity = $item['quantity'];
                    $itemModel->unit = $item['unit'] ?? '';
                    $itemModel->estimated_cost = $item['estimated_cost'] ?? null;
                    $itemModel->actual_cost = $item['actual_cost'] ?? null;
                    $itemModel->status = $item['status'];
                    if (!$itemModel->create()) {
                        throw new \Exception('Unable to create order item.', 500);
                    }
                    $this->logAction('order_item', $itemModel->id, 'create', AuthMiddleware::$userId, $item);
                    $createdItems[] = [
                        'id' => $itemModel->id,
                        'order_id' => $this->order->id,
                        'product_name' => $itemModel->product_name,
                        'quantity' => $itemModel->quantity,
                        'unit' => $itemModel->unit,
                        'estimated_cost' => $itemModel->estimated_cost,
                        'actual_cost' => $itemModel->actual_cost,
                        'status' => $itemModel->status,
                    ];
                }
            }

            $this->db->commit();
            $this->logAction('order', $this->order->id, 'create', AuthMiddleware::$userId, $data);
            ResponseHelper::success('Order created successfully', [
                'id' => $this->order->id,
                'created_by' => $this->order->created_by,
                'assigned_to' => $this->order->assigned_to,
                'status' => $this->order->status,
                'created_at' => $this->order->created_at,
                'completed_at' => $this->order->completed_at,
                'items' => $createdItems,
            ], 201);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $status = $e->getCode() ?: 500;
            if ($status < 100) {
                $status = 500;
            }
            ResponseHelper::error($status, $e->getMessage());
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
            ResponseHelper::success('Order updated successfully', [
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
            ResponseHelper::success('Order deleted successfully');
        } else {
            ResponseHelper::error(500, 'Unable to delete order.');
        }
    }

    public function assignOrder($id)
    {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid order ID.');
            return;
        }
        if (!AuthMiddleware::check()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $actorId = (int)AuthMiddleware::$userId;
        $role = AuthMiddleware::$role;

        $this->order->id = $id;
        $stmt = $this->order->readOne();
        if ($stmt->rowCount() !== 1) {
            ResponseHelper::error(404, 'Order not found.');
            return;
        }
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role === 'assistant') {
            $targetId = isset($data['user_id']) ? (int)$data['user_id'] : $actorId;
            if ($targetId !== $actorId) {
                ResponseHelper::error(403, 'Assistants can only self-assign.');
                return;
            }
            if (!empty($current['assigned_to']) && (int)$current['assigned_to'] !== $actorId) {
                ResponseHelper::error(403, 'Order already assigned.');
                return;
            }
        } elseif ($role === 'owner') {
            if (!isset($data['user_id'])) {
                ResponseHelper::error(400, 'user_id is required.');
                return;
            }
            $targetId = (int)$data['user_id'];
            if (!Validator::validateInt($targetId)) {
                ResponseHelper::error(400, 'Invalid user_id.');
                return;
            }
            $targetRole = $this->getUserRole($targetId);
            if ($targetRole !== 'assistant') {
                ResponseHelper::error(400, 'user_id must be an assistant.');
                return;
            }
        } else {
            ResponseHelper::error(403, 'Unauthorized.');
            return;
        }

        $this->order->assigned_to = $targetId;
        $this->order->status = 'assigned';
        $this->order->completed_at = $current['completed_at'];
        if ($this->order->update()) {
            $this->logAction('order', $id, 'assign', $actorId, ['user_id' => $targetId]);
            ResponseHelper::success('Order assigned successfully', [
                'id' => $this->order->id,
                'assigned_to' => $this->order->assigned_to,
                'assigned_by' => $actorId,
            ]);
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
            ResponseHelper::success('Order marked as completed successfully');
        } else {
            ResponseHelper::error(500, 'Unable to complete order.');
        }
    }
}
