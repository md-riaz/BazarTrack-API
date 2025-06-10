<?php
// File: src/Database.php

namespace App\Core;

use PDO;
use PDOException;
use App\Core\ResponseHelper;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?: 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?: 'test_db';
        $this->username = $_ENV['DB_USER'] ?: 'root';
        $this->password = $_ENV['DB_PASS'] ?: '';
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
            ResponseHelper::error(500, 'Database Connection Error: ' . $exception->getMessage());
            exit;
        }
        return $this->conn;
    }
}
