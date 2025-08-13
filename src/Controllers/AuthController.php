<?php
// File: src/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\ResponseHelper;
use App\Models\Token;
use PDO;
use App\Core\AuthMiddleware;

class AuthController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['email']) || empty($data['password'])) {
            ResponseHelper::error(400, 'Email and password are required.');
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error(400, 'Invalid email format.');
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
                $token->created_at = TIMESTAMP;
                $token->create();
                ResponseHelper::success('Login successful', [
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
        ResponseHelper::error(401, 'Invalid credentials.');
    }

    public function logout() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $header = $_SERVER['HTTP_AUTHORIZATION'];
        $tokenValue = str_replace('Bearer ', '', $header);
        $token = new Token($this->db);
        $token->revoke($tokenValue);
        ResponseHelper::success('Logged out successfully');
    }

    public function me() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $tokenValue = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $token = new Token($this->db);
        $userId = $token->findUserId($tokenValue);
        $stmt = $this->db->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        ResponseHelper::success('User information retrieved', ['user' => $user]);
    }

    public function refresh() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $oldTokenValue = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $token = new Token($this->db);
        $userId = $token->findUserId($oldTokenValue);
        $token->revoke($oldTokenValue);

        $newValue = bin2hex(random_bytes(16));
        $token->user_id = $userId;
        $token->token = $newValue;
        $token->created_at = TIMESTAMP;
        $token->create();

        ResponseHelper::success('Token refreshed successfully', ['token' => $newValue]);
    }
}
