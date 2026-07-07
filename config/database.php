<?php
/**
 * Database Configuration
 * Connection settings for Dr. Feelgood App
 */

class Database {
    private $host = getenv('DB_HOST') ?: 'localhost';
    private $db_name = getenv('DB_NAME') ?: 'silverwebbuzz_in_eclinicpro_home';
    private $user = getenv('DB_USER') ?: 'silverwebbuzz_in_eclinicpro_home';
    private $password = getenv('DB_PASSWORD') ?: '';
    private $charset = 'utf8mb4';

    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=' . $this->charset;
            $this->conn = new PDO($dsn, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log('PDO Connection Error: ' . $e->getMessage());
            return null;
        }

        return $this->conn;
    }
}
