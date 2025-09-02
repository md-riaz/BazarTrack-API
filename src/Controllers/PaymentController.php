<?php
// File: src/Controllers/PaymentController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Payment;
use App\Models\Wallet;
use App\Core\RoleGuard;
use App\Core\LoggerTrait;

use App\Core\ResponseHelper;
use App\Core\Validator;

use App\Core\AuthMiddleware;
use PDO;

class PaymentController {
    use LoggerTrait;
    private $db;
    private $payment;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->payment = new Payment($this->db);
    }

    public function processRequest($method, $id = null) {
        if (!AuthMiddleware::check()) {
            return;
        }
        switch ($method) {
            case 'GET':
                $this->getPayments();
                break;
            case 'POST':
                $this->createPayment();
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getPayments() {
        $filters = [];
        if (isset($_GET['user_id'])) {
            if (!Validator::validateInt($_GET['user_id'])) {
                ResponseHelper::error(400, 'Invalid user_id.');
                return;
            }
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (isset($_GET['owner_id'])) {
            if (!Validator::validateInt($_GET['owner_id'])) {
                ResponseHelper::error(400, 'Invalid owner_id.');
                return;
            }
            $filters['owner_id'] = (int)$_GET['owner_id'];
        }
        if (isset($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }
        if (isset($_GET['from'])) {
            $filters['from'] = $_GET['from'];
        }
        if (isset($_GET['to'])) {
            $filters['to'] = $_GET['to'];
        }

        [$limit, $cursor] = \App\Core\Pagination::getParams();
        $stmt = $this->payment->readAll($filters, $limit, $cursor);
        $payments_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payments_arr[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'owner_id' => $row['owner_id'],
                'amount' => $row['amount'],
                'type' => $row['type'],
                'created_at' => $row['created_at'],
            ];
        }
        ResponseHelper::success('Payments retrieved successfully', $payments_arr);
    }

    private function createPayment()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $required = ['user_id', 'amount', 'type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                ResponseHelper::error(400, "$field is required.");
                return;
            }
        }

        if (!Validator::validateInt($data['user_id']) ||
            !Validator::validateFloat($data['amount'])) {
            ResponseHelper::error(400, 'Invalid data format.');
            return;
        }

        if (!in_array($data['type'], ['credit', 'debit', 'wallet'], true)) {
            ResponseHelper::error(400, 'Invalid type.');
            return;
        }

        $guard = new RoleGuard();
        $wallet = new Wallet($this->db);

        if ($data['type'] === 'credit') {
            if (!$guard->checkRole(AuthMiddleware::$userId, ['owner'])) {
                ResponseHelper::error(403, 'Only owners can credit wallets.');
                return;
            }
            $ownerId = AuthMiddleware::$userId;
            $paymentAmount = abs($data['amount']);
            $paymentType = 'credit';
            $walletDelta = $paymentAmount;
            $transactionType = 'credit';
        } elseif ($data['type'] === 'debit') {
            if (!$guard->checkRole(AuthMiddleware::$userId, ['assistant'])) {
                ResponseHelper::error(403, 'Only assistants can record expenses.');
                return;
            }
            if (empty($data['owner_id']) || !Validator::validateInt($data['owner_id'])) {
                ResponseHelper::error(400, 'owner_id is required.');
                return;
            }
            $ownerId = (int)$data['owner_id'];
            $paymentAmount = abs($data['amount']);
            $paymentType = 'debit';
            $walletDelta = -$paymentAmount;
            $transactionType = 'debit';
        } else { // wallet
            if (!$guard->checkRole(AuthMiddleware::$userId, ['assistant'])) {
                ResponseHelper::error(403, 'Only assistants can refund via wallet.');
                return;
            }
            if (empty($data['owner_id']) || !Validator::validateInt($data['owner_id'])) {
                ResponseHelper::error(400, 'owner_id is required.');
                return;
            }
            $ownerId = (int)$data['owner_id'];
            $paymentAmount = -abs($data['amount']);
            $paymentType = 'credit';
            $walletDelta = -abs($data['amount']);
            $transactionType = 'debit';
        }

        $this->payment->user_id = $data['user_id'];
        $this->payment->owner_id = $ownerId;
        $this->payment->amount = $paymentAmount;
        $this->payment->type = $paymentType;
        $this->payment->created_at = TIMESTAMP;

        if ($this->payment->create()) {
            $wallet->updateBalance($data['user_id'], $walletDelta);
            $wallet->addTransaction($data['user_id'], abs($data['amount']), $transactionType, TIMESTAMP);
            $this->logAction('payment', $this->payment->id, 'create', AuthMiddleware::$userId, $data);
            ResponseHelper::success('Payment created successfully', [
                'id' => $this->payment->id,
                'user_id' => $this->payment->user_id,
                'owner_id' => $this->payment->owner_id,
                'amount' => $this->payment->amount,
                'type' => $this->payment->type,
                'created_at' => $this->payment->created_at,
            ], 201);
        } else {
            ResponseHelper::error(500, 'Unable to create payment.');
        }
    }
}
