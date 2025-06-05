<?php
// File: src/Controllers/AnalyticsController.php

namespace App\Controllers;

use App\Core\Database;
use PDO;

class AnalyticsController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function dashboard() {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM users");
        $totalUsers = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM orders");
        $totalOrders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM payments");
        $totalPayments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments");
        $totalRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'total_revenue' => $totalRevenue
        ]);
    }

    public function reports() {
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

        echo json_encode([
            'orders_by_month' => $ordersByMonth,
            'revenue_by_month' => $revenueByMonth
        ]);
    }
}
