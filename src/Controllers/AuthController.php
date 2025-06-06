<?php
// File: src/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Database;
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
                $token  = bin2hex(random_bytes(16));
                $expiry = date('Y-m-d H:i:s', time() + 3600);
                $insert = $this->db->prepare('INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
                $insert->bindParam(1, $row['id'], PDO::PARAM_INT);
                $insert->bindParam(2, $token);
                $insert->bindParam(3, $expiry);
                $insert->execute();

                echo json_encode([
                    'token' => $token,
                    'user'  => [
                        'id'    => $row['id'],
                        'name'  => $row['name'],
                        'email' => $row['email'],
                        'role'  => $row['role'],
                    ],
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
            echo json_encode(['message' => 'No token provided.']);
            return;
        }

        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $stmt  = $this->db->prepare('DELETE FROM tokens WHERE token = ?');
        $stmt->bindParam(1, $token);
        $stmt->execute();

        echo json_encode(['message' => 'Logged out successfully.']);
    }

    public function me() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'No token provided.']);
            return;
        }

        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $query = 'SELECT u.id, u.name, u.email, u.role, t.expires_at, t.id as token_id
                  FROM tokens t JOIN users u ON t.user_id = u.id
                  WHERE t.token = ? LIMIT 1';
        $stmt  = $this->db->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strtotime($row['expires_at']) > time()) {
                echo json_encode([
                    'id'    => $row['id'],
                    'name'  => $row['name'],
                    'email' => $row['email'],
                    'role'  => $row['role'],
                ]);
                return;
            }

            $delete = $this->db->prepare('DELETE FROM tokens WHERE id = ?');
            $delete->bindParam(1, $row['token_id']);
            $delete->execute();
        }

        http_response_code(401);
        echo json_encode(['message' => 'Invalid or expired token.']);
    }

    public function refresh() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(['message' => 'No token provided.']);
            return;
        }

        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $stmt  = $this->db->prepare('SELECT id, user_id, expires_at FROM tokens WHERE token = ? LIMIT 1');
        $stmt->bindParam(1, $token);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strtotime($row['expires_at']) > time()) {
                $newToken  = bin2hex(random_bytes(16));
                $newExpiry = date('Y-m-d H:i:s', time() + 3600);
                $update    = $this->db->prepare('UPDATE tokens SET token = ?, expires_at = ? WHERE id = ?');
                $update->bindParam(1, $newToken);
                $update->bindParam(2, $newExpiry);
                $update->bindParam(3, $row['id']);
                $update->execute();
                echo json_encode(['token' => $newToken]);
                return;
            }

            $delete = $this->db->prepare('DELETE FROM tokens WHERE id = ?');
            $delete->bindParam(1, $row['id']);
            $delete->execute();
        }

        http_response_code(401);
        echo json_encode(['message' => 'Invalid or expired token.']);
    }
}
