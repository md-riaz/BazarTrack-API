<?php
// File: src/Controllers/WalletController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Wallet;
use App\Core\ResponseHelper;
use App\Core\Validator;
use PDO;

class WalletController {
    private $db;
    private $wallet;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->wallet = new Wallet($this->db);
    }

    public function processRequest($method, $user_id = null) {
        switch ($method) {
            case 'GET':
                if ($user_id) {
                    if (!Validator::validateInt($user_id)) {
                        ResponseHelper::error(400, 'Invalid user ID.');
                        break;
                    }
                    $this->getBalance($user_id);
                } else {
                    ResponseHelper::error(400, 'User ID required.');
                }
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getBalance($user_id) {
        if (!Validator::validateInt($user_id)) {
            ResponseHelper::error(400, 'Invalid user ID.');
            return;
        }

        $stmt = $this->wallet->getBalance($user_id);
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["user_id" => $user_id, "balance" => $row['balance']]);
        } else {
            ResponseHelper::error(404, 'Wallet not found.');
        }
    }

    public function getTransactions($user_id) {
        if (!Validator::validateInt($user_id)) {
            ResponseHelper::error(400, 'Invalid user ID.');
            return;
        }

        $stmt = $this->wallet->readTransactions($user_id);
        $transactions_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions_arr[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'amount' => $row['amount'],
                'type' => $row['type'],
                'created_at' => $row['created_at'],
            ];
        }
        echo json_encode($transactions_arr);
    }
}
