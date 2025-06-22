<?php
namespace App\Core;

use App\Models\HistoryLog;
use PDO;

trait LoggerTrait
{
    private function logAction(string $entityType, int $entityId, string $action, int $userId, array $snapshot): void
    {
        $database = new Database();
        $db = $database->getConnection();
        $log = new HistoryLog($db);
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->action = $action;
        $log->changed_by_user_id = $userId;
        $log->timestamp = TIMESTAMP;
        $log->data_snapshot = $snapshot;
        $log->create();
    }
}
