<?php
// File: src/Controllers/AnalyticsController.php

namespace App\Controllers;

use App\Core\Database;
use PDO;
use App\Core\AuthMiddleware;

class AnalyticsController {
    private $db;

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

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM orders");
        $totalOrders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM payments");
        $totalPayments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments");
        $totalRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        ResponseHelper::success('Dashboard analytics retrieved successfully', [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'total_revenue' => $totalRevenue
        ]);
    }

    public function reports() {
        if (!AuthMiddleware::check()) {
            return;
        }
        $ordersStmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count " .
            "FROM orders GROUP BY month ORDER BY month"
        );
        $ordersByMonth = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        $revenueStmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, " .
            "SUM(amount) AS revenue FROM payments GROUP BY month ORDER BY month"
        );
        $revenueByMonth = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

        ResponseHelper::success('Monthly reports retrieved successfully', [
            'orders_by_month' => $ordersByMonth,
            'revenue_by_month' => $revenueByMonth
        ]);
    }
}
