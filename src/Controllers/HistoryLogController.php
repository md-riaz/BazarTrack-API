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
                if ($entityType !== null && $entityId !== null) {
                    if (!Validator::validateInt($entityId)) {
                        ResponseHelper::error(400, 'Invalid entity ID.');
                        return;
                    }
                    $this->getLogsByEntity($entityType, (int)$entityId);
                } elseif ($entityType !== null) {
                    $this->getLogsByEntityType($entityType);
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
        [$limit, $cursor] = \App\Core\Pagination::getParams();
        if ($changedBy !== null) {
            if (!Validator::validateInt($changedBy)) {
                ResponseHelper::error(400, 'Invalid user ID.');
                return;
            }
            $stmt = $this->log->readByUser((int)$changedBy, $limit, $cursor);
        } else {
            $stmt = $this->log->readAll($limit, $cursor);
        }
        $logs_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs_arr[] = [
                'id' => $row['id'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'action' => $row['action'],
                'changed_by_user_id' => $row['changed_by_user_id'],
                'changed_by_user_name' => $row['changed_by_user_name'],
                'timestamp' => $row['timestamp'],
                'data_snapshot' => json_decode($row['data_snapshot']),
            ];
        }
        ResponseHelper::success('History logs retrieved successfully', $logs_arr);
    }

    private function getLogsByEntityType($type) {
        $this->log->entity_type = $type;
        [$limit, $cursor] = \App\Core\Pagination::getParams();
        $stmt = $this->log->readByEntityType($limit, $cursor);
        $logs_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs_arr[] = [
                'id' => $row['id'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'action' => $row['action'],
                'changed_by_user_id' => $row['changed_by_user_id'],
                'changed_by_user_name' => $row['changed_by_user_name'],
                'timestamp' => $row['timestamp'],
                'data_snapshot' => json_decode($row['data_snapshot']),
            ];
        }
        ResponseHelper::success('Entity type history logs retrieved successfully', $logs_arr);
    }

    private function getLogsByEntity($type, $entityId) {
        $this->log->entity_type = $type;
        $this->log->entity_id = (int)$entityId;
        [$limit, $cursor] = \App\Core\Pagination::getParams();
        $stmt = $this->log->readByEntity($limit, $cursor);
        $logs_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs_arr[] = [
                'id' => $row['id'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'action' => $row['action'],
                'changed_by_user_id' => $row['changed_by_user_id'],
                'changed_by_user_name' => $row['changed_by_user_name'],
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
            $stmt = $this->db->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
            $stmt->bindParam(1, $this->log->changed_by_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $changedByUserName = $stmt->fetch(PDO::FETCH_COLUMN) ?: null;

            ResponseHelper::success('History log created successfully', [
                'id' => $this->log->id,
                'entity_type' => $this->log->entity_type,
                'entity_id' => $this->log->entity_id,
                'action' => $this->log->action,
                'changed_by_user_id' => $this->log->changed_by_user_id,
                'changed_by_user_name' => $changedByUserName,
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
