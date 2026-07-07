<?php
/**
 * Medicine Model
 * Handles master medicines list
 */

namespace App\Models;

use PDO;

class Medicine extends BaseModel {
    protected $table = 'master_medicines';

    /**
     * Search medicines by name (for autocomplete)
     */
    public function search($query, $limit = 20) {
        $limit = (int)$limit;
        $sql = "SELECT id, name, usage_count
                FROM {$this->table}
                WHERE name LIKE ?
                ORDER BY usage_count DESC, name ASC
                LIMIT {$limit}";
        $stmt = $this->query($sql, ['%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top medicines by usage (for initial dropdown)
     */
    public function getTop($limit = 50) {
        $limit = (int)$limit;
        $sql = "SELECT id, name, usage_count
                FROM {$this->table}
                ORDER BY usage_count DESC, name ASC
                LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert new medicine or increment usage count if exists
     */
    public function upsert($name) {
        $name = trim($name);
        if ($name === '' || strlen($name) < 2) return;

        $sql = "INSERT INTO {$this->table} (name, usage_count)
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE usage_count = usage_count + 1";
        $this->query($sql, [$name]);
    }

    /**
     * Upsert multiple medicines from a comma-separated string
     */
    public function upsertFromString($medicinsStr) {
        if (empty(trim($medicinsStr))) return;
        $names = array_filter(array_map('trim', explode(',', $medicinsStr)));
        foreach ($names as $name) {
            if (strlen($name) >= 2) {
                $this->upsert($name);
            }
        }
    }
}
