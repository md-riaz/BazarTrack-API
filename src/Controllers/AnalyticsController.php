<?php
// File: src/Controllers/AnalyticsController.php

namespace App\Controllers;

use App\Core\Database;
use PDO;
use App\Core\AuthMiddleware;
use App\Core\ResponseHelper;

class AnalyticsController {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function dashboard() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM users");
        $totalUsers = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $ownerId = AuthMiddleware::$userId;

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM orders WHERE created_by = ?");
        $stmt->execute([$ownerId]);
        $totalOrders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM payments WHERE owner_id = ?");
        $stmt->execute([$ownerId]);
        $totalPayments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE type = 'credit' AND owner_id = ?");
        $stmt->execute([$ownerId]);
        $totalExpense = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        ResponseHelper::success('Dashboard analytics retrieved successfully', [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'total_expense' => $totalExpense
        ]);
    }

    public function reports() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $ownerId = AuthMiddleware::$userId;
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "strftime('%Y-%m', created_at)";
        $start = date('Y-m-01 00:00:00', strtotime('-5 months'));

        $ordersStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, COUNT(*) AS count FROM orders WHERE created_by = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $ordersStmt->execute([$ownerId, $start]);
        $ordersByMonth = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        $expenseStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, SUM(amount) AS expense FROM payments WHERE type = 'debit' AND owner_id = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $expenseStmt->execute([$ownerId, $start]);
        $expenseByMonth = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
        ResponseHelper::success('Monthly reports retrieved successfully', [
            'orders_by_month' => $ordersByMonth,
            'expense_by_month' => $expenseByMonth
        ]);
    }

    public function assistantDashboard() {
        if (!AuthMiddleware::check()) {
            return;
        }
        if (AuthMiddleware::$role !== 'assistant') {
            ResponseHelper::error(403, 'Unauthorized');
            return;
        }

        $assistantId = AuthMiddleware::$userId;

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM orders WHERE assigned_to = ?");
        $stmt->execute([$assistantId]);
        $totalOrders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM payments WHERE user_id = ?");
        $stmt->execute([$assistantId]);
        $totalPayments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE type = 'debit' AND user_id = ?");
        $stmt->execute([$assistantId]);
        $totalExpense = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "strftime('%Y-%m', created_at)";
        $start = date('Y-m-01 00:00:00', strtotime('-5 months'));

        $ordersStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, COUNT(*) AS count FROM orders WHERE assigned_to = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $ordersStmt->execute([$assistantId, $start]);
        $ordersByMonth = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        $expenseStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, COALESCE(SUM(amount),0) AS expense FROM transactions WHERE type = 'debit' AND user_id = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $expenseStmt->execute([$assistantId, $start]);
        $expenseByMonth = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

        ResponseHelper::success('Assistant dashboard analytics retrieved successfully', [
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'total_expense' => $totalExpense,
            'orders_by_month' => $ordersByMonth,
            'expense_by_month' => $expenseByMonth,
        ]);
    }

    public function assistantSummary($userId) {
        if (!AuthMiddleware::check()) {
            return;
        }

        $ownerId = AuthMiddleware::$userId;

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM orders WHERE assigned_to = ? AND created_by = ?");
        $stmt->execute([$userId, $ownerId]);
        $totalOrders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE user_id = ? AND type = 'debit' AND owner_id = ?");
        $stmt->execute([$userId, $ownerId]);
        $totalExpense = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "strftime('%Y-%m', created_at)";
        $start = date('Y-m-01 00:00:00', strtotime('-5 months'));

        $ordersStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, COUNT(*) AS count FROM orders WHERE assigned_to = ? AND created_by = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $ordersStmt->execute([$userId, $ownerId, $start]);
        $ordersByMonth = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        $expenseStmt = $this->db->prepare(
            "SELECT $monthExpr AS month, COALESCE(SUM(amount),0) AS expense FROM payments WHERE user_id = ? AND type = 'debit' AND owner_id = ? AND created_at >= ? GROUP BY month ORDER BY month"
        );
        $expenseStmt->execute([$userId, $ownerId, $start]);
        $expenseByMonth = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

        ResponseHelper::success('Assistant analytics retrieved successfully', [
            'total_orders' => $totalOrders,
            'total_expense' => $totalExpense,
            'orders_by_month' => $ordersByMonth,
            'expense_by_month' => $expenseByMonth,
        ]);
    }
}
