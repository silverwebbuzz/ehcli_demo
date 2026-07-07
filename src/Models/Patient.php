<?php
/**
 * Patient Model
 * Handles patient data operations
 */

namespace App\Models;

use PDO;

class Patient extends BaseModel {
    protected $table = 'patient';

    /**
     * Get patient by ID with all related information
     */
    public function getWithDetails($id) {
        $sql = "SELECT
                    p.*,
                    ai.*
                FROM {$this->table} p
                LEFT JOIN additional_info ai ON p.id = ai.p_id
                WHERE p.id = ?";

        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get patient with progress reports
     */
    public function getWithReports($id, $limit = 50) {
        $limit = (int)$limit;

        $sql = "SELECT
                    p.*,
                    pr.id as report_id,
                    pr.date as report_date,
                    pr.medicins,
                    pr.amt
                FROM {$this->table} p
                LEFT JOIN progress_report pr ON p.id = pr.p_id
                WHERE p.id = ?
                ORDER BY pr.date DESC
                LIMIT {$limit}";

        $stmt = $this->query($sql, [$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search patients by name
     */
    public function findByPhone($phone) {
        $sql = "SELECT id, patient_id, fname, lname, contact_no
                FROM {$this->table} WHERE contact_no = ? LIMIT 1";
        $stmt = $this->query($sql, [$phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function searchByName($name) {
        $sql = "SELECT id, fname, lname, contact_no, dob, gender, chief
                FROM {$this->table}
                WHERE CONCAT(fname, ' ', lname) LIKE ?
                OR fname LIKE ?
                OR lname LIKE ?
                OR contact_no LIKE ?
                ORDER BY fname, lname
                LIMIT 20";

        $searchTerm = "%{$name}%";
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all patients
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT id, patient_id, fname, lname, contact_no, dob, age, gender, mrg_status, chief, dor
                FROM {$this->table}
                ORDER BY fname, lname";

        if ($limit) {
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated patients with optional search — server-side
     */
    public function getPaginated($page = 1, $limit = 25, $search = '') {
        $limit  = (int)$limit;
        $offset = ((int)$page - 1) * $limit;
        $params = [];

        $where = '';
        if ($search !== '') {
            $where = "WHERE CONCAT(fname,' ',lname) LIKE ?
                         OR patient_id LIKE ?
                         OR contact_no LIKE ?";
            $s = "%{$search}%";
            $params = [$s, $s, $s];
        }

        $sql = "SELECT id, patient_id, fname, lname, contact_no, age, gender, mrg_status, dor
                FROM {$this->table}
                {$where}
                ORDER BY id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total patient count with optional search
     */
    public function getTotalCount($search = '') {
        $params = [];
        $where  = '';
        if ($search !== '') {
            $where = "WHERE CONCAT(fname,' ',lname) LIKE ?
                         OR patient_id LIKE ?
                         OR contact_no LIKE ?";
            $s = "%{$search}%";
            $params = [$s, $s, $s];
        }
        $sql    = "SELECT COUNT(*) as count FROM {$this->table} {$where}";
        $stmt   = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Add new patient
     */
    /**
     * Next business patient_id = current highest patient_id + 1.
     * Kept independent of the auto-increment row `id` so it continues the
     * existing (imported) patient_id sequence, e.g. 15073 → 15074.
     */
    public function nextPatientId(): int {
        $stmt = $this->query("SELECT MAX(patient_id) AS max_pid FROM {$this->table}");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['max_pid'] ?? 0) + 1;
    }

    /**
     * Quick-create a minimal patient record from appointment data (name + phone only)
     * Used when walk-in or booking patient is not in the system yet
     */
    public function createQuick($name, $phone, $chief = '', $extra = []) {
        $parts = explode(' ', trim($name), 2);
        $fname = $parts[0] ?? $name;
        $lname = $parts[1] ?? '';

        // Continue the business patient_id sequence (highest + 1)
        $data = [
            'patient_id' => $this->nextPatientId(),
            'fname'      => $fname,
            'lname'      => $lname,
            'contact_no' => $phone,
            'dor'        => date('Y-m-d'),
        ];
        if ($chief !== '') $data['chief'] = $chief;

        // Optional demographics captured at booking time (New Patient flow)
        $allowedExtra = ['age', 'gender', 'dob'];
        foreach ($allowedExtra as $k) {
            if (isset($extra[$k]) && trim((string)$extra[$k]) !== '') {
                $data[$k] = $extra[$k];
            }
        }

        return $this->insert($data); // gets MySQL auto-increment id
    }

    public function create($data) {
        $requiredFields = ['fname', 'contact_no'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field] ?? null)) {
                throw new \Exception("Field '{$field}' is required");
            }
        }

        // Set default values
        $data['dor'] = !empty($data['dor']) ? $data['dor'] : date('Y-m-d');

        // Convert empty date/numeric fields to NULL so MySQL doesn't get ''
        foreach (['dob', 'age'] as $field) {
            if (isset($data[$field]) && trim((string)$data[$field]) === '') {
                $data[$field] = null;
            }
        }

        // Honour a manually entered patient_id only if it's a real number,
        // otherwise continue the business patient_id sequence (highest + 1).
        $manualId = isset($data['patient_id']) && trim((string)$data['patient_id']) !== ''
            ? (int)$data['patient_id'] : null;

        // Whitelist to real table columns so stray POST keys can't break the INSERT
        $allowed = ['dor','fname','lname','address','city','state','zip',
                    'contact_no','dob','age','gender','mrg_status','veg','religion',
                    'education','occupation','refered_by','chief'];
        $data = array_intersect_key($data, array_flip($allowed));

        $data['patient_id'] = $manualId ?? $this->nextPatientId();

        return $this->insert($data);
    }

    /**
     * Update patient info
     */
    public function updatePatient($id, $data) {
        // Don't allow updating critical fields
        unset($data['id'], $data['patient_id']);

        // Convert empty date fields to NULL so MySQL doesn't get ''
        $dateFields = ['dob', 'dor'];
        foreach ($dateFields as $field) {
            if (array_key_exists($field, $data) && trim($data[$field]) === '') {
                $data[$field] = null;
            }
        }

        // Convert empty numeric fields to NULL
        $numericFields = ['age'];
        foreach ($numericFields as $field) {
            if (array_key_exists($field, $data) && trim((string)$data[$field]) === '') {
                $data[$field] = null;
            }
        }

        // Convert empty ENUM fields to NULL — MySQL truncates '' for ENUM columns
        // ("Data truncated for column 'gender'"), so store NULL when left blank.
        $enumFields = ['gender', 'mrg_status', 'veg'];
        foreach ($enumFields as $field) {
            if (array_key_exists($field, $data) && trim((string)$data[$field]) === '') {
                $data[$field] = null;
            }
        }

        $this->update($id, $data);
        return true;
    }

    /**
     * Permanently delete a patient and ALL related records
     * (progress reports, additional info, appointments) in one transaction.
     * Returns a breakdown of how many rows were removed per table.
     */
    public function deleteWithRelated($id) {
        $id = (int)$id;

        $patient = $this->getById($id);
        if (!$patient) {
            throw new \Exception("Patient not found");
        }

        $this->db->beginTransaction();
        try {
            $deleted = [];

            $stmt = $this->db->prepare("DELETE FROM progress_report WHERE p_id = ?");
            $stmt->execute([$id]);
            $deleted['reports'] = $stmt->rowCount();

            $stmt = $this->db->prepare("DELETE FROM additional_info WHERE p_id = ?");
            $stmt->execute([$id]);
            $deleted['additional_info'] = $stmt->rowCount();

            $stmt = $this->db->prepare("DELETE FROM appointments WHERE patient_id = ?");
            $stmt->execute([$id]);
            $deleted['appointments'] = $stmt->rowCount();

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            $deleted['patient'] = $stmt->rowCount();

            $this->db->commit();
            return $deleted;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("Failed to delete patient: " . $e->getMessage());
        }
    }

    /**
     * Get recent patients (last N)
     */
    public function getRecent($limit = 10) {
        $limit = (int)$limit;

        $sql = "SELECT id, patient_id, fname, lname, contact_no, dob, age, gender, mrg_status, chief, dor
                FROM {$this->table}
                ORDER BY dor DESC
                LIMIT {$limit}";

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>