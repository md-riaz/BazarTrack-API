<?php
// File: src/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\ResponseHelper;
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
                // Generate a simple token (for example purposes only)
                $token = bin2hex(random_bytes(16));
                // Store/associate token in a simple tokens table (not implemented here)
                echo json_encode(["token" => $token, "user" => ["id" => $row['id'], "name" => $row['name'], "email" => $row['email'], "role" => $row['role']]]);
                return;
            }
        }
        ResponseHelper::error(401, 'Invalid credentials.');
    }

    public function logout() {
        // Invalidate the token (not implemented: would remove from tokens table)
        echo json_encode(["message" => "Logged out successfully."]);
    }

    public function me() {
        // Retrieve token from headers and return user info (not fully implemented)
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            ResponseHelper::error(401, 'No token provided.');
            return;
        }
        $token = str_replace("Bearer ", "", $_SERVER['HTTP_AUTHORIZATION']);
        // Lookup token in tokens table and return user info; placeholder response:
        echo json_encode(["user" => ["id" => 1, "name" => "Example User", "email" => "user@example.com"]]);
    }

    public function refresh() {
        // In real scenario, verify old token and issue new one.
        // Placeholder: return new token
        $newToken = bin2hex(random_bytes(16));
        echo json_encode(["token" => $newToken]);
    }
}
