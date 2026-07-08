<?php
/**
 * HomeoIntake Model
 * Homeopathy intake questionnaire: tokenized public links + auto miasm/thermal scoring.
 */

namespace App\Models;

use PDO;

class HomeoIntake extends BaseModel {
    protected $table = 'homeo_intake';

    /** Load the shared questionnaire schema (tabs/fields/scoring rules). */
    public static function schema(): array {
        return require __DIR__ . '/../intake_schema.php';
    }

    /**
     * Create a new intake link for a patient.
     * Returns ['id' => .., 'token' => ..].
     */
    public function createLink(?int $patientId, ?int $createdBy, int $validDays = 7): array {
        $token = bin2hex(random_bytes(20)); // 40 hex chars
        $id = $this->insert([
            'token'      => $token,
            'patient_id' => $patientId ?: null,
            'created_by' => $createdBy ?: null,
            'status'     => 'sent',
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$validDays} days")),
        ]);
        return ['id' => (int)$id, 'token' => $token];
    }

    public function findByToken(string $token): ?array {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE token = ? LIMIT 1", [$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByPatient(int $patientId): array {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE patient_id = ? ORDER BY created_at DESC",
            [$patientId]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** True once a link can no longer be filled. */
    public static function isExpired(array $row): bool {
        if (in_array($row['status'], ['submitted', 'locked'], true)) return true;
        return !empty($row['expires_at']) && strtotime($row['expires_at']) < time();
    }

    /**
     * Store submitted answers, compute the score, and lock the record.
     * $answers is the raw associative array of field name => value(s).
     */
    public function submit(int $id, array $answers): void {
        [$scores, $thermal] = self::score($answers);
        $this->update($id, [
            'answers'      => json_encode($answers, JSON_UNESCAPED_UNICODE),
            'miasm_scores' => json_encode($scores),
            'thermal'      => $thermal,
            'status'       => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Heuristic scorer. Walks the schema, tallies miasm weights from the
     * selected scored options, and returns [percentages, thermal].
     *
     * This is a decision AID, not a diagnosis — the result view labels it so.
     *
     * @return array{0: array<string,int>, 1: string}
     */
    public static function score(array $answers): array {
        $schema = self::schema();
        $totals = array_fill_keys(array_keys($schema['miasms']), 0);
        $thermal = '';

        foreach ($schema['tabs'] as $tab) {
            foreach ($tab['fields'] as $field) {
                if (empty($field['options'])) continue;
                $given = $answers[$field['name']] ?? null;
                if ($given === null || $given === '') continue;
                $selected = is_array($given) ? $given : [$given];

                foreach ($field['options'] as $opt) {
                    if (!in_array($opt['value'], $selected, true)) continue;
                    if (!empty($opt['miasm'])) {
                        foreach ($opt['miasm'] as $m => $w) {
                            if (isset($totals[$m])) $totals[$m] += (int)$w;
                        }
                    }
                    if (!empty($opt['thermal']) && $opt['thermal'] !== 'neutral' && $thermal === '') {
                        $thermal = $opt['thermal'];
                    }
                }
            }
        }

        $sum = array_sum($totals);
        $pct = [];
        foreach ($totals as $m => $v) {
            $pct[$m] = $sum > 0 ? (int)round($v * 100 / $sum) : 0;
        }
        return [$pct, $thermal];
    }

    /** Decode the stored answers/scores JSON on a row for display. */
    public static function decode(array $row): array {
        $row['answers']      = $row['answers'] ? json_decode($row['answers'], true) : [];
        $row['miasm_scores'] = $row['miasm_scores'] ? json_decode($row['miasm_scores'], true) : [];
        return $row;
    }
}
