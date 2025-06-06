<?php
// File: src/Controllers/OrderItemController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\OrderItem;
use App\Models\Wallet;
use App\Core\RoleGuard;
use App\Core\LoggerTrait;
use App\Core\ResponseHelper;
use App\Core\Validator;

use App\Core\AuthMiddleware;

use PDO;

class OrderItemController {
    use LoggerTrait;
    private $db;
    private $item;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->item = new OrderItem($this->db);
    }

    public function processRequest($method, $id = null, $parentId = null) {
        if (!AuthMiddleware::check()) {
            return;
        }
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
                    ResponseHelper::error(400, 'Item ID required for PUT.');
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteItem($id);
                } else {
                    ResponseHelper::error(400, 'Item ID required for DELETE.');
                }
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
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
        if (!Validator::validateInt($orderId)) {
            ResponseHelper::error(400, 'Invalid order ID.');
            return;
        }
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
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid item ID.');
            return;
        }
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
            ResponseHelper::error(404, 'Item not found.');
        }
    }

    private function createItem()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $required = ['order_id', 'product_name', 'quantity', 'status', 'user_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                ResponseHelper::error(400, "$field is required.");
                return;
            }
        }

        if (!Validator::validateInt($data['order_id']) || !Validator::validateInt($data['user_id']) || !Validator::validateFloat($data['quantity'])) {
            ResponseHelper::error(400, 'Invalid input format.');
            return;
        }

        if ((isset($data['estimated_cost']) && $data['estimated_cost'] !== null && !Validator::validateFloat($data['estimated_cost'])) ||
            (isset($data['actual_cost']) && $data['actual_cost'] !== null && !Validator::validateFloat($data['actual_cost']))) {
            ResponseHelper::error(400, 'Invalid cost format.');
            return;
        }

        $guard = new RoleGuard();
        if (!$guard->checkRole($data['user_id'], ['owner'])) {
            ResponseHelper::error(403, 'Only owners can add items.'); 
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
            $this->logAction('order_item', $this->item->id, 'create', (int)$data['user_id'], $data);
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
            ResponseHelper::error(500, 'Unable to create item.');
        }
    }

    private function updateItem($id)
    {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid item ID.');
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $required = ['product_name', 'quantity', 'status', 'user_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                ResponseHelper::error(400, "$field is required.");
                return;
            }
        }

        if (!Validator::validateInt($data['user_id']) || !Validator::validateFloat($data['quantity'])) {
            ResponseHelper::error(400, 'Invalid input format.');
            return;
        }

        if ((isset($data['estimated_cost']) && $data['estimated_cost'] !== null && !Validator::validateFloat($data['estimated_cost'])) ||
            (isset($data['actual_cost']) && $data['actual_cost'] !== null && !Validator::validateFloat($data['actual_cost']))) {
            ResponseHelper::error(400, 'Invalid cost format.');
            return;
        }

        $guard = new RoleGuard();
        if (!$guard->checkRole($data['user_id'], ['assistant', 'owner'])) {
            ResponseHelper::error(403, 'Permission denied.');
            return;
        }

        $wallet = new Wallet($this->db);
        $this->item->id = $id;
        $this->item->product_name = $data['product_name'];
        $this->item->quantity = $data['quantity'];
        $this->item->unit = $data['unit'] ?? '';
        $this->item->estimated_cost = $data['estimated_cost'] ?? null;
        $this->item->actual_cost = $data['actual_cost'] ?? null;
        $this->item->status = $data['status'];
        if ($this->item->update()) {
            if (!empty($data['actual_cost'])) {
                $wallet->updateBalance($data['user_id'], -$data['actual_cost']);
                $wallet->addTransaction($data['user_id'], $data['actual_cost'], 'debit', date('Y-m-d H:i:s'));
            }
            $this->logAction('order_item', $id, 'update', (int)$data['user_id'], $data);
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
            ResponseHelper::error(500, 'Unable to update item.');
        }
    }

    private function deleteItem($id)
    {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid item ID.');
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
        if (!$guard->checkRole($data['user_id'], ['owner'])) {
            ResponseHelper::error(403, 'Only owners can delete items.');
            return;
        }
        $this->item->id = $id;
        if ($this->item->delete()) {
            $this->logAction('order_item', $id, 'delete', (int)$data['user_id'], $data);
            echo json_encode(["message" => "Item deleted."]); 
        } else {
            ResponseHelper::error(500, 'Unable to delete item.');
        }
    }
}
