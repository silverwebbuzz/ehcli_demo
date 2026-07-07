<?php
/**
 * Base Database Model
 * Provides common database operations for all models
 */

namespace App\Models;

use PDO;

class BaseModel {
    protected $db;
    protected $table;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Execute a SELECT query
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Get all records from a table
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";

        if ($limit) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get record count
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Insert record
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        $this->query($sql, array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET {$set} WHERE id = ?";
        $this->query($sql, $values);
    }

    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->query($sql, [$id]);
    }

    /**
     * Search in table
     */
    public function search($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} LIKE ?";
        $stmt = $this->query($sql, ["%{$value}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}