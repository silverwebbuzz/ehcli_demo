<?php
/**
 * Expense Model
 * Clinic Expense Manager: CRUD + reporting aggregations.
 */

namespace App\Models;

use PDO;

class Expense extends BaseModel {
    protected $table = 'expense';

    /** Fixed category list used by the manager form and report grouping. */
    const CATEGORIES = [
        'Rent', 'Salaries', 'Utilities', 'Medicines / Stock', 'Equipment',
        'Marketing', 'Maintenance', 'Travel', 'Taxes / Fees', 'Miscellaneous',
    ];

    const PAYMENT_MODES = ['cash', 'card', 'upi', 'bank', 'cheque'];

    /** Recent expenses, optionally filtered by date range and/or category. */
    public function listExpenses(?string $from = null, ?string $to = null, ?string $category = null, int $limit = 500): array {
        $where = [];
        $params = [];
        if ($from) { $where[] = 'expense_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'expense_date <= ?'; $params[] = $to; }
        if ($category) { $where[] = 'category = ?'; $params[] = $category; }
        $clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $limit = (int)$limit;
        $sql = "SELECT * FROM {$this->table} {$clause} ORDER BY expense_date DESC, id DESC LIMIT {$limit}";
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Create an expense from sanitized data. */
    public function createExpense(array $data): string {
        return $this->insert([
            'expense_date' => $data['expense_date'],
            'category'     => $data['category'],
            'description'  => $data['description'] ?? null,
            'vendor'       => $data['vendor'] ?? null,
            'amount'       => $data['amount'],
            'payment_mode' => $data['payment_mode'] ?? 'cash',
            'created_by'   => $data['created_by'] ?? null,
        ]);
    }

    /** Update an existing expense. */
    public function updateExpense($id, array $data): void {
        $this->update((int)$id, [
            'expense_date' => $data['expense_date'],
            'category'     => $data['category'],
            'description'  => $data['description'] ?? null,
            'vendor'       => $data['vendor'] ?? null,
            'amount'       => $data['amount'],
            'payment_mode' => $data['payment_mode'] ?? 'cash',
        ]);
    }

    // ── Reporting aggregations (mirror the Report model shape) ──────────────

    /** Totals for a date range. */
    public function summary(string $from, string $to): array {
        $sql = "SELECT
                    COUNT(*) as entries,
                    COALESCE(SUM(amount), 0) as total,
                    COALESCE(AVG(NULLIF(amount,0)), 0) as avg_amount,
                    COALESCE(MAX(amount), 0) as max_amount
                FROM {$this->table}
                WHERE expense_date BETWEEN ? AND ?";
        return $this->query($sql, [$from, $to])->fetch(PDO::FETCH_ASSOC);
    }

    /** Spend grouped by category within a range — for pie/bar. */
    public function byCategory(string $from, string $to): array {
        $sql = "SELECT category, COALESCE(SUM(amount),0) as total, COUNT(*) as entries
                FROM {$this->table}
                WHERE expense_date BETWEEN ? AND ?
                GROUP BY category
                ORDER BY total DESC";
        return $this->query($sql, [$from, $to])->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Daily spend within a range. */
    public function byDay(string $from, string $to): array {
        $sql = "SELECT expense_date as day, COALESCE(SUM(amount),0) as total, COUNT(*) as entries
                FROM {$this->table}
                WHERE expense_date BETWEEN ? AND ?
                GROUP BY expense_date
                ORDER BY expense_date ASC";
        return $this->query($sql, [$from, $to])->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Weekly spend within a range. */
    public function byWeek(string $from, string $to): array {
        $sql = "SELECT YEARWEEK(expense_date, 1) as yw, MIN(expense_date) as week_start,
                       COALESCE(SUM(amount),0) as total, COUNT(*) as entries
                FROM {$this->table}
                WHERE expense_date BETWEEN ? AND ?
                GROUP BY YEARWEEK(expense_date, 1)
                ORDER BY yw ASC";
        return $this->query($sql, [$from, $to])->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Monthly spend for a year. */
    public function byMonth(int $year): array {
        $sql = "SELECT DATE_FORMAT(expense_date,'%Y-%m') as month,
                       COALESCE(SUM(amount),0) as total, COUNT(*) as entries
                FROM {$this->table}
                WHERE YEAR(expense_date) = ?
                GROUP BY DATE_FORMAT(expense_date,'%Y-%m')
                ORDER BY month ASC";
        return $this->query($sql, [$year])->fetchAll(PDO::FETCH_ASSOC);
    }
}
