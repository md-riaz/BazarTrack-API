<?php
// File: src/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Token;
use PDO;

class AuthController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["message" => "Email and password are required."]);
            return;
        }
        // Validate against users table; passwords are stored using password_hash
        $query = "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $data['email']);
        $stmt->execute();
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data['password'], $row['password'])) {
                $tokenValue = bin2hex(random_bytes(16));
                $token = new Token($this->db);
                $token->user_id = $row['id'];
                $token->token = $tokenValue;
                $token->created_at = date('Y-m-d H:i:s');
                $token->create();
                echo json_encode([
                    'token' => $tokenValue,
                    'user' => [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'role' => $row['role']
                    ]
                ]);
                return;
            }
        }
        http_response_code(401);
        echo json_encode(["message" => "Invalid credentials."]);
    }

    public function logout() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }
        $header = $_SERVER['HTTP_AUTHORIZATION'];
        $tokenValue = str_replace('Bearer ', '', $header);
        $token = new Token($this->db);
        $token->revoke($tokenValue);
        echo json_encode(['message' => 'Logged out successfully.']);
    }

    public function me() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }
        $tokenValue = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $token = new Token($this->db);
        $userId = $token->findUserId($tokenValue);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }
        $stmt = $this->db->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['user' => $user]);
    }

    public function refresh() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }
        $oldTokenValue = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $token = new Token($this->db);
        $userId = $token->findUserId($oldTokenValue);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }
        $token->revoke($oldTokenValue);

        $newValue = bin2hex(random_bytes(16));
        $token->user_id = $userId;
        $token->token = $newValue;
        $token->created_at = date('Y-m-d H:i:s');
        $token->create();

        echo json_encode(['token' => $newValue]);
    }
}
