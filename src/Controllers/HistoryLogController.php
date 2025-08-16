<?php
// File: src/Controllers/HistoryLogController.php

namespace App\Controllers;

use App\Core\Database;
use App\Models\HistoryLog;
use App\Core\ResponseHelper;
use App\Core\Validator;
use PDO;
use App\Core\AuthMiddleware;

class HistoryLogController {
    private $db;
    private $log;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->log = new HistoryLog($this->db);
    }

    public function processRequest($method, $id = null, $entityType = null, $entityId = null) {
        if (!AuthMiddleware::check()) {
            return;
        }
        switch ($method) {
            case 'GET':
                if ($entityType && $entityId) {
                    $this->getLogsByEntity($entityType, $entityId);
                } else {
                    $this->getLogs();
                }
                break;
            case 'POST':
                $this->createLog();
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteLog($id);
                } else {
                    ResponseHelper::error(400, 'Log ID required for DELETE.');
                }
                break;
            default:
                ResponseHelper::error(405, 'Method not allowed.');
                break;
        }
    }

    private function getLogs() {
        $changedBy = $_GET['changed_by'] ?? null;
        if ($changedBy !== null) {
            if (!Validator::validateInt($changedBy)) {
                ResponseHelper::error(400, 'Invalid user ID.');
                return;
            }
            $stmt = $this->log->readByUser((int)$changedBy);
        } else {
            $stmt = $this->log->readAll();
        }
        $logs_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs_arr[] = [
                'id' => $row['id'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'action' => $row['action'],
                'changed_by_user_id' => $row['changed_by_user_id'],
                'timestamp' => $row['timestamp'],
                'data_snapshot' => json_decode($row['data_snapshot']),
            ];
        }
        ResponseHelper::success('History logs retrieved successfully', $logs_arr);
    }

    private function getLogsByEntity($type, $entityId) {
        $this->log->entity_type = $type;
        $this->log->entity_id = $entityId;
        $stmt = $this->log->readByEntity();
        $logs_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs_arr[] = [
                'id' => $row['id'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'action' => $row['action'],
                'changed_by_user_id' => $row['changed_by_user_id'],
                'timestamp' => $row['timestamp'],
                'data_snapshot' => json_decode($row['data_snapshot']),
            ];
        }
        ResponseHelper::success('Entity history logs retrieved successfully', $logs_arr);
    }

    private function createLog() {
        $data = json_decode(file_get_contents("php://input"), true);
        $required = ['entity_type', 'entity_id', 'action', 'data_snapshot'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                ResponseHelper::error(400, "$field is required.");
                return;
            }
        }

        if (!Validator::validateInt($data['entity_id'])) {
            ResponseHelper::error(400, 'Invalid input format.');
            return;
        }
        $this->log->entity_type = $data['entity_type'];
        $this->log->entity_id = $data['entity_id'];
        $this->log->action = $data['action'];
        $this->log->changed_by_user_id = AuthMiddleware::$userId;
        $this->log->timestamp = TIMESTAMP;
        $this->log->data_snapshot = $data['data_snapshot'];
        if ($this->log->create()) {
            ResponseHelper::success('History log created successfully', [
                'id' => $this->log->id,
                'entity_type' => $this->log->entity_type,
                'entity_id' => $this->log->entity_id,
                'action' => $this->log->action,
                'changed_by_user_id' => $this->log->changed_by_user_id,
                'timestamp' => $this->log->timestamp,
                'data_snapshot' => $this->log->data_snapshot,
            ], 201);
        } else {
            ResponseHelper::error(500, 'Unable to create log.');
        }
    }

    private function deleteLog($id) {
        if (!Validator::validateInt($id)) {
            ResponseHelper::error(400, 'Invalid log ID.');
            return;
        }
        $this->log->id = $id;
        if ($this->log->delete()) {
            ResponseHelper::success('Log deleted successfully');
        } else {
            ResponseHelper::error(500, 'Unable to delete log.');
        }
    }
}
