<?php
// File: src/Controllers/PaymentController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Payment;
use PDO;

class PaymentController {
    private $db;
    private $payment;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->payment = new Payment($this->db);
    }

    public function processRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                $this->getPayments();
                break;
            case 'POST':
                $this->createPayment();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }

    private function getPayments() {
        $stmt = $this->payment->readAll();
        $payments_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payments_arr[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'amount' => $row['amount'],
                'type' => $row['type'],
                'created_at' => $row['created_at'],
            ];
        }
        echo json_encode($payments_arr);
    }

    private function createPayment() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['user_id']) || empty($data['amount']) || empty($data['type']) || empty($data['created_at'])) {
            http_response_code(400);
            echo json_encode(["message" => "user_id, amount, type, and created_at are required."]);
            return;
        }
        $this->payment->user_id = $data['user_id'];
        $this->payment->amount = $data['amount'];
        $this->payment->type = $data['type'];
        $this->payment->created_at = $data['created_at'];
        if ($this->payment->create()) {
            http_response_code(201);
            echo json_encode([
                'id' => $this->payment->id,
                'user_id' => $this->payment->user_id,
                'amount' => $this->payment->amount,
                'type' => $this->payment->type,
                'created_at' => $this->payment->created_at,
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to create payment."]);
        }
    }
}
