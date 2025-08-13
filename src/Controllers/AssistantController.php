<?php
// File: src/Controllers/AssistantController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Core\ResponseHelper;
use PDO;
use App\Core\AuthMiddleware;

class AssistantController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function processRequest($method) {
        if (!AuthMiddleware::check()) {
            return;
        }
        switch ($method) {
            case 'GET':
                $includeBalance = isset($_GET['with_balance']) && in_array($_GET['with_balance'], ['1', 'true'], true);
                $this->getAssistants($includeBalance);
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getAssistants($includeBalance) {
        $stmt = $this->user->readAssistants($includeBalance);
        $assistants = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assistant = [
                'id' => $row['id'],
                'name' => $row['name'],
            ];
            if ($includeBalance) {
                $assistant['balance'] = $row['balance'];
            }
            $assistants[] = $assistant;
        }
        ResponseHelper::success('Assistants retrieved successfully', $assistants);
    }
}

