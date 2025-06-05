<?php
// File: src/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $db_name = 'test_db';
    private $username = 'root';
    private $password = '';
    private $conn;

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
