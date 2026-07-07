<?php
namespace App\Models;
use PDO;

class Appointment extends BaseModel {
    protected $table = 'appointments';

    /** Today's queue — all appointments for a date */
    public function getByDate($date) {
        $sql = "SELECT a.*, p.fname, p.lname, p.contact_no,
                       pr.id   AS report_id,
                       pr.amt  AS report_amt,
                       pr.payment_type,
                       pr.payment_status
                FROM {$this->table} a
                LEFT JOIN patient p ON a.patient_id = p.id
                LEFT JOIN progress_report pr ON pr.id = (
                    SELECT pr2.id FROM progress_report pr2
                    WHERE pr2.p_id = a.patient_id AND pr2.date = a.appt_date
                    ORDER BY pr2.id DESC LIMIT 1
                )
                WHERE a.appt_date = ?
                ORDER BY a.token_number ASC, a.slot_time ASC";
        $stmt = $this->query($sql, [$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * The patient's currently in-consultation appointment for a date, if any.
     * Used so the visit page can show "Finish" even when the doctor opened the
     * patient directly (not via the queue's Call action).
     */
    public function getActiveForPatient($patientId, $date) {
        $sql = "SELECT id, token_number, status
                FROM {$this->table}
                WHERE patient_id = ? AND appt_date = ? AND status = 'in_consultation'
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $this->query($sql, [$patientId, $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Next available token number for a date */
    public function nextToken($date) {
        $sql = "SELECT MAX(token_number) as max_token FROM {$this->table} WHERE appt_date = ?";
        $stmt = $this->query($sql, [$date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row['max_token'] ?? 0) + 1;
    }

    /** How many bookings exist for a specific date+time slot */
    public function countSlot($date, $time) {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}
                WHERE appt_date = ? AND TIME_FORMAT(slot_time,'%H:%i') = ?
                AND status NOT IN ('cancelled','no_show')";
        $stmt = $this->query($sql, [$date, $time]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }

    /** Check if a date is a clinic holiday / closed day */
    public function isClosedDate($date) {
        $sql = "SELECT id FROM clinic_closed_dates WHERE date = ? LIMIT 1";
        $stmt = $this->query($sql, [$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /** List all closed dates */
    public function getClosedDates() {
        $sql = "SELECT id, date, reason FROM clinic_closed_dates ORDER BY date ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Add a closed date */
    public function addClosedDate($date, $reason = '') {
        $sql = "INSERT IGNORE INTO clinic_closed_dates (date, reason) VALUES (?, ?)";
        $this->query($sql, [$date, $reason]);
        return $this->db->lastInsertId();
    }

    /** Remove a closed date */
    public function removeClosedDate($id) {
        $sql = "DELETE FROM clinic_closed_dates WHERE id = ?";
        $this->query($sql, [(int)$id]);
    }

    /** Booked slot times for a date — returns HH:MM strings to match generateSlots() output */
    public function bookedSlots($date) {
        $sql = "SELECT TIME_FORMAT(slot_time, '%H:%i') as slot_time
                FROM {$this->table}
                WHERE appt_date = ? AND status NOT IN ('cancelled','no_show')
                AND slot_time IS NOT NULL";
        $stmt = $this->query($sql, [$date]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'slot_time');
    }

    /** Create walk-in token */
    public function createWalkin($data, $userId = null) {
        $date = $data['appt_date'] ?? date('Y-m-d');
        $insert = [
            'patient_id'      => !empty($data['patient_id']) ? (int)$data['patient_id'] : null,
            'appt_date'       => $date,
            'slot_time'       => null,
            'token_number'    => $this->nextToken($date),
            'type'            => 'walkin',
            'status'          => 'waiting',
            'patient_name'    => $data['patient_name'] ?? null,
            'patient_phone'   => $data['patient_phone'] ?? null,
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'is_new_patient'  => $data['is_new_patient'] ?? 0,
            'is_followup'     => $data['is_followup'] ?? 0,
            'created_by'      => $userId,
        ];
        return $this->insert($insert);
    }

    /** Create pre-booked appointment (public page) */
    public function createPrebooked($data) {
        $date = $data['appt_date'];
        $insert = [
            'patient_id'      => !empty($data['patient_id']) ? (int)$data['patient_id'] : null,
            'appt_date'       => $date,
            'slot_time'       => $data['slot_time'],
            'token_number'    => $this->nextToken($date),
            'type'            => 'prebooked',
            'status'          => 'waiting',
            'patient_name'    => $data['patient_name'] ?? null,
            'patient_phone'   => $data['patient_phone'] ?? null,
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'is_new_patient'  => $data['is_new_patient'] ?? 0,
            'is_followup'     => $data['is_followup'] ?? 0,
            'created_by'      => null,
        ];
        return $this->insert($insert);
    }

    /** Update appointment status */
    public function updateStatus($id, $status) {
        $now = date('Y-m-d H:i:s');
        $extra = [];
        if ($status === 'in_consultation') $extra['called_at']    = $now; // Doctor called patient
        if ($status === 'completed')       $extra['completed_at'] = $now; // Consultation done
        $data = array_merge(['status' => $status], $extra);
        $this->update($id, $data);
    }

    /** Get single appointment with patient join */
    public function getByIdFull($id) {
        $sql = "SELECT a.*, p.fname, p.lname, p.contact_no
                FROM {$this->table} a
                LEFT JOIN patient p ON a.patient_id = p.id
                WHERE a.id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Stats for today */
    public function todayStats($date) {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(status IN ('waiting','arrived')) as waiting,
                    SUM(status='arrived') as arrived,
                    SUM(status='in_consultation') as in_consultation,
                    SUM(status='completed') as completed,
                    SUM(type='walkin') as walkins,
                    SUM(type='prebooked') as prebooked
                FROM {$this->table} WHERE appt_date = ?";
        $stmt = $this->query($sql, [$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Appointments for a date range (week / month view) */
    public function getByRange($from, $to) {
        $sql = "SELECT a.*, p.fname, p.lname, p.contact_no,
                       pr.id   AS report_id,
                       pr.amt  AS report_amt,
                       pr.payment_type,
                       pr.payment_status
                FROM {$this->table} a
                LEFT JOIN patient p ON a.patient_id = p.id
                LEFT JOIN progress_report pr ON pr.id = (
                    SELECT pr2.id FROM progress_report pr2
                    WHERE pr2.p_id = a.patient_id AND pr2.date = a.appt_date
                    ORDER BY pr2.id DESC LIMIT 1
                )
                WHERE a.appt_date BETWEEN ? AND ?
                ORDER BY a.appt_date ASC, a.token_number ASC, a.slot_time ASC";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Stats for a date range */
    public function rangeStats($from, $to) {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(status='waiting') as waiting,
                    SUM(status='in_consultation') as in_consultation,
                    SUM(status='completed') as completed,
                    SUM(status='cancelled') as cancelled,
                    SUM(status='no_show') as no_show
                FROM {$this->table} WHERE appt_date BETWEEN ? AND ?";
        $stmt = $this->query($sql, [$from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Recent appointments list for history */
    public function getRecent($limit = 50) {
        $limit = (int)$limit;
        $sql = "SELECT a.*, p.fname, p.lname, p.contact_no
                FROM {$this->table} a
                LEFT JOIN patient p ON a.patient_id = p.id
                ORDER BY a.appt_date DESC, a.token_number ASC
                LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
