<?php
namespace App\Models;
use PDO;

/**
 * Report Model — all analytics queries for the Reports module.
 * All date-range methods accept $from / $to as 'Y-m-d' strings.
 */
class Report extends BaseModel {
    protected $table = 'progress_report';

    // ── helpers ──────────────────────────────────────────────────────────────

    /** Return [from, to] for a named period relative to today.
     *  $year is used when period='year' so selecting a past year works correctly. */
    public static function periodDates(string $period, int $year = 0): array {
        $today = date('Y-m-d');
        $y     = $year > 0 ? $year : (int)date('Y');
        switch ($period) {
            case 'today':
                return [$today, $today];
            case 'week':
                $mon = date('Y-m-d', strtotime('monday this week'));
                $sun = date('Y-m-d', strtotime('sunday this week'));
                return [$mon, $sun];
            case 'month':
                return [date('Y-m-01'), date('Y-m-t')];
            case 'year':
                return ["{$y}-01-01", "{$y}-12-31"];
            default:
                return [$today, $today];
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // INCOME
    // ══════════════════════════════════════════════════════════════════════════

    /** Total revenue + consultation count for a date range */
    public function incomeSummary(string $from, string $to): array {
        $sql = "SELECT
                    COUNT(*) as consultations,
                    COALESCE(SUM(amt), 0) as total,
                    COALESCE(AVG(NULLIF(amt,0)), 0) as avg_fee
                FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND amt > 0";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Daily revenue breakdown within a range — for bar/line chart */
    public function incomeByDay(string $from, string $to): array {
        $sql = "SELECT
                    date as day,
                    COALESCE(SUM(amt), 0) as total,
                    COUNT(*) as consultations
                FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND amt > 0
                GROUP BY date
                ORDER BY date ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Monthly revenue breakdown for a year */
    public function incomeByMonth(int $year): array {
        $sql = "SELECT
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COALESCE(SUM(amt), 0) as total,
                    COUNT(*) as consultations
                FROM {$this->table}
                WHERE YEAR(date) = ?
                AND amt > 0
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Weekly revenue for a range */
    public function incomeByWeek(string $from, string $to): array {
        $sql = "SELECT
                    YEARWEEK(date, 1) as yw,
                    MIN(date) as week_start,
                    COALESCE(SUM(amt), 0) as total,
                    COUNT(*) as consultations
                FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND amt > 0
                GROUP BY YEARWEEK(date, 1)
                ORDER BY yw ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PATIENT ANALYTICS
    // ══════════════════════════════════════════════════════════════════════════

    /** New patient registrations grouped by day */
    public function newPatientsByDay(string $from, string $to): array {
        $sql = "SELECT dor as day, COUNT(*) as count
                FROM patient
                WHERE dor BETWEEN ? AND ?
                GROUP BY dor ORDER BY dor ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** New patient registrations grouped by month */
    public function newPatientsByMonth(int $year): array {
        $sql = "SELECT DATE_FORMAT(dor,'%Y-%m') as month, COUNT(*) as count
                FROM patient
                WHERE YEAR(dor) = ?
                GROUP BY month ORDER BY month ASC";
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Gender distribution */
    public function genderSplit(): array {
        $sql = "SELECT gender, COUNT(*) as count FROM patient GROUP BY gender";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Patient age group distribution */
    public function ageGroups(): array {
        $sql = "SELECT
                    CASE
                        WHEN age < 13  THEN '0-12'
                        WHEN age < 19  THEN '13-18'
                        WHEN age < 31  THEN '19-30'
                        WHEN age < 46  THEN '31-45'
                        WHEN age < 61  THEN '46-60'
                        ELSE '60+'
                    END as age_group,
                    COUNT(*) as count
                FROM patient
                WHERE age IS NOT NULL AND age > 0
                GROUP BY age_group
                ORDER BY MIN(age)";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Top chief complaints */
    public function topComplaints(int $limit = 10): array {
        $sql = "SELECT chief, COUNT(*) as count
                FROM patient
                WHERE chief IS NOT NULL AND TRIM(chief) != ''
                GROUP BY chief
                ORDER BY count DESC
                LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** New vs returning patients (those with >1 report) for a range */
    public function newVsReturning(string $from, string $to): array {
        $sql = "SELECT
                    SUM(CASE WHEN visit_count = 1 THEN 1 ELSE 0 END) as new_patients,
                    SUM(CASE WHEN visit_count > 1  THEN 1 ELSE 0 END) as returning_patients
                FROM (
                    SELECT p_id, COUNT(*) as visit_count
                    FROM {$this->table}
                    WHERE date BETWEEN ? AND ?
                    GROUP BY p_id
                ) t";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // QUEUE / OPERATIONS
    // ══════════════════════════════════════════════════════════════════════════

    /** Daily appointment totals and statuses for a range */
    public function appointmentsByDay(string $from, string $to): array {
        $sql = "SELECT
                    appt_date as day,
                    COUNT(*) as total,
                    SUM(status='completed') as completed,
                    SUM(status='no_show') as no_show,
                    SUM(status='cancelled') as cancelled,
                    SUM(type='walkin') as walkins,
                    SUM(type='prebooked') as prebooked
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                GROUP BY appt_date ORDER BY appt_date ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Average consultation duration (called_at → completed_at) in minutes */
    public function avgConsultTime(string $from, string $to): array {
        $sql = "SELECT
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)), 1) as avg_minutes,
                    MIN(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as min_minutes,
                    MAX(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as max_minutes,
                    COUNT(*) as sample_count
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                AND called_at IS NOT NULL AND completed_at IS NOT NULL
                AND TIMESTAMPDIFF(MINUTE, called_at, completed_at) BETWEEN 1 AND 120";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Busiest day-of-week breakdown */
    public function busyDays(string $from, string $to): array {
        $sql = "SELECT
                    DAYNAME(appt_date) as day_name,
                    DAYOFWEEK(appt_date) as dow,
                    COUNT(*) as total
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                AND status NOT IN ('cancelled','no_show')
                GROUP BY dow, day_name ORDER BY dow";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Busiest time slots */
    public function busySlots(string $from, string $to, int $limit = 10): array {
        $sql = "SELECT
                    TIME_FORMAT(slot_time,'%H:%i') as slot,
                    COUNT(*) as total
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                AND slot_time IS NOT NULL
                AND status NOT IN ('cancelled','no_show')
                GROUP BY slot ORDER BY total DESC LIMIT {$limit}";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** No-show & cancellation summary */
    public function noShowRate(string $from, string $to): array {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(status='no_show') as no_show,
                    SUM(status='cancelled') as cancelled,
                    SUM(status='completed') as completed
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // MEDICINES
    // ══════════════════════════════════════════════════════════════════════════

    /** Top prescribed medicines (raw text field — split by comma) */
    public function topMedicines(string $from, string $to, int $limit = 15): array {
        // medicins is a comma-separated text field — we pull all rows and count in PHP
        $sql = "SELECT medicins FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND medicins IS NOT NULL AND TRIM(medicins) != ''";
        $stmt = $this->query($sql, [$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = [];
        foreach ($rows as $row) {
            $parts = preg_split('/[,;\n]+/', $row['medicins']);
            foreach ($parts as $med) {
                $med = trim($med);
                if ($med === '') continue;
                $key = strtolower($med);
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }
        arsort($counts);
        $result = [];
        foreach (array_slice($counts, 0, $limit, true) as $name => $count) {
            $result[] = ['medicine' => $name, 'count' => $count];
        }
        return $result;
    }

    /** Prescriptions per day for a range */
    public function prescriptionsByDay(string $from, string $to): array {
        $sql = "SELECT date as day, COUNT(*) as prescriptions
                FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND medicins IS NOT NULL AND TRIM(medicins) != ''
                GROUP BY date ORDER BY date ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Prescriptions per week for a range */
    public function prescriptionsByWeek(string $from, string $to): array {
        $sql = "SELECT YEARWEEK(date,1) as yw, MIN(date) as week_start, COUNT(*) as prescriptions
                FROM {$this->table}
                WHERE date BETWEEN ? AND ?
                AND medicins IS NOT NULL AND TRIM(medicins) != ''
                GROUP BY yw ORDER BY yw ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Prescriptions per month for a year */
    public function prescriptionsByMonth(int $year): array {
        $sql = "SELECT DATE_FORMAT(date,'%Y-%m') as month, COUNT(*) as prescriptions
                FROM {$this->table}
                WHERE YEAR(date) = ?
                AND medicins IS NOT NULL AND TRIM(medicins) != ''
                GROUP BY month ORDER BY month ASC";
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOCTOR PRODUCTIVITY
    // ══════════════════════════════════════════════════════════════════════════

    /** Patients seen per day */
    public function patientsSeen(string $from, string $to): array {
        $sql = "SELECT appt_date as day, COUNT(*) as seen
                FROM appointments
                WHERE appt_date BETWEEN ? AND ? AND status='completed'
                GROUP BY appt_date ORDER BY appt_date ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Patients seen per week */
    public function patientsSeenByWeek(string $from, string $to): array {
        $sql = "SELECT YEARWEEK(appt_date,1) as yw, MIN(appt_date) as week_start, COUNT(*) as seen
                FROM appointments
                WHERE appt_date BETWEEN ? AND ? AND status='completed'
                GROUP BY yw ORDER BY yw ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Patients seen per month for a year */
    public function patientsSeenByMonth(int $year): array {
        $sql = "SELECT DATE_FORMAT(appt_date,'%Y-%m') as month, COUNT(*) as seen
                FROM appointments
                WHERE YEAR(appt_date)=? AND status='completed'
                GROUP BY month ORDER BY month ASC";
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Appointments per week */
    public function appointmentsByWeek(string $from, string $to): array {
        $sql = "SELECT YEARWEEK(appt_date,1) as yw, MIN(appt_date) as week_start,
                    COUNT(*) as total,
                    SUM(status='completed') as completed,
                    SUM(status='no_show') as no_show,
                    SUM(status='cancelled') as cancelled
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                GROUP BY yw ORDER BY yw ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Appointments per month for a year */
    public function appointmentsByMonth(int $year): array {
        $sql = "SELECT DATE_FORMAT(appt_date,'%Y-%m') as month,
                    COUNT(*) as total,
                    SUM(status='completed') as completed,
                    SUM(status='no_show') as no_show,
                    SUM(status='cancelled') as cancelled
                FROM appointments
                WHERE YEAR(appt_date)=?
                GROUP BY month ORDER BY month ASC";
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** New patients by week */
    public function newPatientsByWeek(string $from, string $to): array {
        $sql = "SELECT YEARWEEK(dor,1) as yw, MIN(dor) as week_start, COUNT(*) as count
                FROM patient WHERE dor BETWEEN ? AND ?
                GROUP BY yw ORDER BY yw ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Summary: total seen, avg per day, peak day */
    public function productivitySummary(string $from, string $to): array {
        $sql = "SELECT
                    COUNT(*) as total_seen,
                    COUNT(DISTINCT appt_date) as working_days,
                    ROUND(COUNT(*) / NULLIF(COUNT(DISTINCT appt_date),0), 1) as avg_per_day
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                AND status = 'completed'";
        $stmt = $this->query($sql, [$from, $to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Peak day
        $sql2 = "SELECT appt_date, COUNT(*) as cnt
                 FROM appointments
                 WHERE appt_date BETWEEN ? AND ? AND status='completed'
                 GROUP BY appt_date ORDER BY cnt DESC LIMIT 1";
        $stmt2 = $this->query($sql2, [$from, $to]);
        $peak = $stmt2->fetch(PDO::FETCH_ASSOC);
        $row['peak_day']   = $peak['appt_date'] ?? null;
        $row['peak_count'] = $peak['cnt'] ?? 0;
        return $row;
    }

    /** Consult time trend by day */
    public function consultTimeTrend(string $from, string $to): array {
        $sql = "SELECT
                    appt_date as day,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)), 1) as avg_minutes
                FROM appointments
                WHERE appt_date BETWEEN ? AND ?
                AND called_at IS NOT NULL AND completed_at IS NOT NULL
                AND TIMESTAMPDIFF(MINUTE, called_at, completed_at) BETWEEN 1 AND 120
                GROUP BY appt_date ORDER BY appt_date ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
