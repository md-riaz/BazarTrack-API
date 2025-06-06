<?php
// File: src/Controllers/UserController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Core\ResponseHelper;
use App\Core\Validator;
use PDO;
use App\Core\AuthMiddleware;

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function processRequest($method, $id = null) {
        if (!AuthMiddleware::check()) {
            return;
        }
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getUser($id);
                } else {
                    $this->getUsers();
                }
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                ResponseHelper::error(405, 'User management is disabled.');
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getUsers() {
        $stmt = $this->user->readAll();
        $users_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users_arr[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'],
            ];
        }
        echo json_encode($users_arr);
    }

    private function getUser($id) {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid user ID.');
            return;
        }
        $this->user->id = $id;
        $stmt = $this->user->readOne();
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'],
            ]);
        } else {
            ResponseHelper::error(404, 'User not found.');
        }
    }

    private function createUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['name']) || empty($data['email'])) {
            ResponseHelper::error(400, 'Name and email are required.');
            return;
        }
        $this->user->name = $data['name'];
        $this->user->email = $data['email'];
        if ($this->user->create()) {
            http_response_code(201);
            echo json_encode([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]);
        } else {
            ResponseHelper::error(500, 'Unable to create user.');
        }
    }

    private function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['name']) || empty($data['email'])) {
            ResponseHelper::error(400, 'Name and email are required.');
            return;
        }
        $this->user->id = $id;
        $this->user->name = $data['name'];
        $this->user->email = $data['email'];
        if ($this->user->update()) {
            echo json_encode([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]);
        } else {
            ResponseHelper::error(500, 'Unable to update user.');
        }
    }

    private function deleteUser($id) {
        $this->user->id = $id;
        if ($this->user->delete()) {
            echo json_encode(["message" => "User deleted."]); 
        } else {
            ResponseHelper::error(500, 'Unable to delete user.');
        }
    }
}
