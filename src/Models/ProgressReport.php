<?php
/**
 * Progress Report Model
 * Handles patient treatment history
 */

namespace App\Models;

use PDO;

class ProgressReport extends BaseModel {
    protected $table = 'progress_report';

    /** Cached check for the optional medicine_details column (added 2026-06-30). */
    private static $hasDetailsCol = null;

    /** Cached check for the optional client_uuid column (added 2026-07-01). */
    private static $hasUuidCol = null;

    /** Cached check for the optional apply_gst column (added 2026-07-15). */
    private static $hasApplyGstCol = null;

    /**
     * Whether the offline-sync idempotency column exists. Lets the app keep
     * working on databases where the migration hasn't been applied yet.
     */
    public function hasClientUuid(): bool {
        if (self::$hasUuidCol === null) {
            try {
                $stmt = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'client_uuid'");
                self::$hasUuidCol = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
            } catch (\Exception $e) {
                self::$hasUuidCol = false;
            }
        }
        return self::$hasUuidCol;
    }

    /**
     * Look up an existing report by its client-generated UUID. Returns the row
     * id (or null). Used by the sync endpoint to deduplicate resubmissions.
     */
    public function findByClientUuid(string $uuid) {
        if (!$this->hasClientUuid()) return null;
        $stmt = $this->query("SELECT id FROM {$this->table} WHERE client_uuid = ? LIMIT 1", [$uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : null;
    }

    /**
     * Whether the per-medicine breakdown column exists. Lets the app keep
     * working on databases where the migration hasn't been applied yet.
     */
    public function hasMedicineDetails(): bool {
        if (self::$hasDetailsCol === null) {
            try {
                $stmt = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'medicine_details'");
                self::$hasDetailsCol = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
            } catch (\Exception $e) {
                self::$hasDetailsCol = false;
            }
        }
        return self::$hasDetailsCol;
    }

    /**
     * Whether the per-visit GST override column exists. Lets the app keep
     * working on databases where the migration hasn't been applied yet.
     */
    public function hasApplyGst(): bool {
        if (self::$hasApplyGstCol === null) {
            try {
                $stmt = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'apply_gst'");
                self::$hasApplyGstCol = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
            } catch (\Exception $e) {
                self::$hasApplyGstCol = false;
            }
        }
        return self::$hasApplyGstCol;
    }

    /** Column list for SELECTs, including optional columns only when present. */
    private function detailsSelect(): string {
        $cols = '';
        if ($this->hasMedicineDetails()) $cols .= ', medicine_details';
        if ($this->hasApplyGst())        $cols .= ', apply_gst';
        return $cols;
    }

    /**
     * Get all progress reports for a patient
     */
    public function getByPatientId($patientId, $limit = 50, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT id, p_id, date, medicins, notes, reports_notes, amt, payment_type, payment_status{$this->detailsSelect()}
                FROM {$this->table}
                WHERE p_id = ?
                ORDER BY date DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->query($sql, [$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Patients who have a visit/progress report saved today, most recent first.
     * Powers the "Today Visited Patients" dashboard section.
     */
    public function getVisitedToday() {
        $sql = "SELECT pr.id, pr.p_id, pr.date, pr.medicins, pr.amt,
                    pr.payment_type, pr.payment_status,
                    p.fname, p.lname, p.patient_id, p.contact_no, p.gender, p.age
                FROM {$this->table} pr
                JOIN patient p ON pr.p_id = p.id
                WHERE DATE(pr.date) = CURDATE()
                ORDER BY pr.id DESC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's (most recent) report for a patient, if any.
     * Used to surface an in-progress visit (e.g. brief notes saved by the
     * Asst. Doctor) on the visit form so the Doctor can continue the same
     * visit instead of starting a new one.
     */
    public function getTodayForPatient($patientId) {
        $sql = "SELECT id, p_id, date, medicins, notes, reports_notes, amt, payment_type, payment_status{$this->detailsSelect()}
                FROM {$this->table}
                WHERE p_id = ? AND DATE(date) = CURDATE()
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $this->query($sql, [$patientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get count of reports for a patient
     */
    public function getPatientReportCount($patientId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE p_id = ?";
        $stmt = $this->query($sql, [$patientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Add progress report
     */
    public function create($patientId, $data) {
        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }
        if (empty($data['medicins'] ?? null) && empty($data['notes'] ?? null) && empty($data['reports_notes'] ?? null)) {
            throw new \Exception("Medicines or notes are required");
        }

        $reportData = [
            'p_id'           => $patientId,
            'date'           => $data['date'] ?? date('Y-m-d H:i:s'),
            'medicins'       => $data['medicins'] ?? '',
            'notes'          => $data['notes']    ?? '',
            'reports_notes'  => $data['reports_notes'] ?? '',
            'amt'            => $data['amt']      ?? 0,
            'payment_type'   => in_array($data['payment_type'] ?? '', ['cash', 'online']) ? $data['payment_type'] : 'cash',
            'payment_status' => in_array($data['payment_status'] ?? '', ['paid', 'remaining']) ? $data['payment_status'] : 'paid',
        ];

        if ($this->hasMedicineDetails() && isset($data['medicine_details'])) {
            $reportData['medicine_details'] = $data['medicine_details'];
        }

        // Per-visit GST override (1 = force on, 0 = force off). Absent = follow settings.
        if ($this->hasApplyGst() && isset($data['apply_gst']) && $data['apply_gst'] !== '') {
            $reportData['apply_gst'] = $data['apply_gst'] === '1' || $data['apply_gst'] === 1 ? 1 : 0;
        }

        // Idempotency key for records created offline and synced later.
        if ($this->hasClientUuid() && !empty($data['client_uuid'])) {
            $reportData['client_uuid'] = $data['client_uuid'];
        }

        return $this->insert($reportData);
    }

    /**
     * Update progress report
     */
    public function updateReport($id, $data) {
        unset($data['id'], $data['p_id']);
        $this->update($id, $data);
        return true;
    }

    /**
     * Get recent reports across all patients
     */
    public function getRecent($limit = 20) {
        $limit = (int)$limit;

        $sql = "SELECT
                    pr.id, pr.p_id, pr.date, pr.medicins, pr.notes, pr.reports_notes, pr.amt,
                    pr.payment_type, pr.payment_status,
                    CONCAT(p.fname, ' ', p.lname) as patient_name
                FROM {$this->table} pr
                JOIN patient p ON pr.p_id = p.id
                ORDER BY pr.date DESC
                LIMIT {$limit}";

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reports by date range
     */
    public function getByDateRange($startDate, $endDate, $limit = null) {
        $sql = "SELECT
                    pr.id, pr.p_id, pr.date, pr.medicins, pr.notes, pr.reports_notes, pr.amt,
                    pr.payment_type, pr.payment_status,
                    CONCAT(p.fname, ' ', p.lname) as patient_name
                FROM {$this->table} pr
                JOIN patient p ON pr.p_id = p.id
                WHERE pr.date BETWEEN ? AND ?
                ORDER BY pr.date DESC";

        if ($limit) {
            $limit = (int)$limit;
            $sql .= " LIMIT {$limit}";
            $stmt = $this->query($sql, [$startDate, $endDate]);
        } else {
            $stmt = $this->query($sql, [$startDate, $endDate]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>