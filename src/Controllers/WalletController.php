<?php
// File: src/Controllers/WalletController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Wallet;
use PDO;
use App\Core\AuthMiddleware;

class WalletController {
    private $db;
    private $wallet;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->wallet = new Wallet($this->db);
    }

    public function processRequest($method, $user_id = null) {
        if (!AuthMiddleware::check()) {
            return;
        }
        switch ($method) {
            case 'GET':
                if ($user_id) {
                    $this->getBalance($user_id);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "User ID required."]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }

    private function getBalance($user_id) {
        $stmt = $this->wallet->getBalance($user_id);
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["user_id" => $user_id, "balance" => $row['balance']]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Wallet not found."]);
        }
    }

    public function getTransactions($user_id) {
        if (!AuthMiddleware::check()) {
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
