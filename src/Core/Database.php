<?php
// File: src/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Load configuration so environment variables from .env are available
        require_once __DIR__ . '/../../config.php';

        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'test_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            http_response_code(500);
            echo json_encode(["error" => "Database Connection Error: " . $exception->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
