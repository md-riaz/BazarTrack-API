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
        // Placeholder: return static stats
        echo json_encode([
            "total_users" => 100,
            "total_orders" => 250,
            "total_payments" => 150,
            "total_revenue" => 50000
        ]);
    }

    public function reports() {
        // Placeholder: return static report data
        echo json_encode([
            "orders_by_month" => [
                ["month" => "2025-01", "count" => 20],
                ["month" => "2025-02", "count" => 30]
            ],
            "revenue_by_month" => [
                ["month" => "2025-01", "revenue" => 5000],
                ["month" => "2025-02", "revenue" => 7000]
            ]
        ]);
    }
}
