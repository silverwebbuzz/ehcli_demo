<?php
/**
 * Expense Controller
 * Clinic Expense Manager: CRUD operations + the expense report.
 */

namespace App\Controllers;

use App\Models\Expense;
use App\Models\Report;

class ExpenseController {
    private $model;

    public function __construct($db) {
        $this->model = new Expense($db);
    }

    /** Manager list with optional filters (from/to/category). */
    public function listExpenses(array $params = []): array {
        $from     = $this->cleanDate($params['from'] ?? null);
        $to       = $this->cleanDate($params['to'] ?? null);
        $category = ($params['category'] ?? '') !== '' ? $params['category'] : null;
        return $this->model->listExpenses($from, $to, $category);
    }

    /** Validate + create/update. Returns a JSON-shaped response array. */
    public function save(array $post, ?int $userId, $id = null): array {
        try {
            $data = $this->validate($post);
            $data['created_by'] = $userId;
            if ($id) {
                $this->model->updateExpense((int)$id, $data);
                return ['success' => true, 'message' => 'Expense updated.'];
            }
            $newId = $this->model->createExpense($data);
            return ['success' => true, 'message' => 'Expense added.', 'id' => $newId];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id): array {
        try {
            $this->model->delete((int)$id);
            return ['success' => true, 'message' => 'Expense deleted.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validate(array $post): array {
        $date = $this->cleanDate($post['expense_date'] ?? '');
        if (!$date) throw new \Exception('Please choose a valid date.');

        $category = trim($post['category'] ?? '');
        if ($category === '' || !in_array($category, Expense::CATEGORIES, true)) {
            throw new \Exception('Please choose a valid category.');
        }

        $amount = (float)($post['amount'] ?? 0);
        if ($amount <= 0) throw new \Exception('Amount must be greater than zero.');

        $mode = trim($post['payment_mode'] ?? 'cash');
        if (!in_array($mode, Expense::PAYMENT_MODES, true)) $mode = 'cash';

        return [
            'expense_date' => $date,
            'category'     => $category,
            'description'  => trim($post['description'] ?? '') ?: null,
            'vendor'       => trim($post['vendor'] ?? '') ?: null,
            'amount'       => round($amount, 2),
            'payment_mode' => $mode,
        ];
    }

    private function cleanDate($v): ?string {
        $v = trim((string)$v);
        if ($v === '') return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    // ── Report ──────────────────────────────────────────────────────────────

    private function resolveDates(array $params): array {
        $period = $params['period'] ?? 'month';
        $year   = (int)($params['year'] ?? date('Y'));
        if ($period === 'custom') {
            $from = $params['from'] ?? date('Y-m-01');
            $to   = $params['to']   ?? date('Y-m-d');
            if ($from > $to) [$from, $to] = [$to, $from];
        } else {
            [$from, $to] = Report::periodDates($period, $year);
        }
        return [$period, $from, $to];
    }

    public function report(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'     => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'summary'    => $this->model->summary($from, $to),
            'byCategory' => $this->model->byCategory($from, $to),
            'byDay'      => $this->model->byDay($from, $to),
            'byWeek'     => $this->model->byWeek($from, $to),
            'byMonth'    => $this->model->byMonth($year),
            'recent'     => $this->model->listExpenses($from, $to, null, 100),
        ];
    }
}
