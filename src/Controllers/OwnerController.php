<?php
// File: src/Controllers/OwnerController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Core\ResponseHelper;
use PDO;
use App\Core\AuthMiddleware;

class OwnerController {
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
                $this->getOwners();
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getOwners() {
        [$limit, $cursor] = \App\Core\Pagination::getParams();
        $stmt = $this->user->readOwners($limit, $cursor);
        $owners = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $owners[] = [
                'id' => $row['id'],
                'name' => $row['name'],
            ];
        }
        ResponseHelper::success('Owners retrieved successfully', $owners);
    }
}
