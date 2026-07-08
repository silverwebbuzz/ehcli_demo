<?php
/**
 * Database Configuration
 * Connection settings for Dr. Feelgood App
 */

class Database {
    private $host;
    private $db_name;
    private $user;
    private $password;
    private $charset = 'utf8mb4';

    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'silverwebbuzz_in_eclinicpro_home';
        $this->user = getenv('DB_USER') ?: 'silverwebbuzz_in_eclinicpro_home';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

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
