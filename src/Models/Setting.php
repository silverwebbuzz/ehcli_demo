<?php
namespace App\Models;
use PDO;

class Setting extends BaseModel {
    protected $table = 'settings';

    public function get($key, $default = null) {
        $sql = "SELECT value FROM {$this->table} WHERE `key` = ?";
        $stmt = $this->query($sql, [$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['value'] : $default;
    }

    public function getAllSettings() {
        $sql = "SELECT `key`, value FROM {$this->table} ORDER BY `key`";
        $stmt = $this->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) $result[$row['key']] = $row['value'];
        return $result;
    }

    public function set($key, $value) {
        $sql = "INSERT INTO {$this->table} (`key`, value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)";
        $this->query($sql, [$key, $value]);
    }

    public function setMany(array $data) {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Generate available time slots for a given date based on settings
     * Returns array of 'HH:MM' strings
     */
    public function generateSlots($date, $extended = false) {
        $dayOfWeek = (int)date('N', strtotime($date)); // 1=Mon, 7=Sun
        $duration  = (int)$this->get('slot_duration_min', 30);
        $slots = [];

        if ($dayOfWeek === 7) {
            // Sunday — no extended hours for Sunday
            if ($this->get('sunday_on', '1') === '1') {
                $slots = array_merge($slots, $this->buildSlots(
                    $this->get('sunday_start', '10:00'),
                    $this->get('sunday_end',   '12:00'),
                    $duration
                ));
            }
        } else {
            // Mon–Sat morning
            if ($this->get('mon_sat_morning_on', '1') === '1') {
                $normalEnd  = $this->get('mon_sat_morning_end', '13:30');
                $extendEnd  = $this->get('extended_morning_end', '14:30');
                $slots = array_merge($slots, $this->buildSlots(
                    $this->get('mon_sat_morning_start', '09:30'),
                    $extended ? $extendEnd : $normalEnd,
                    $duration
                ));
            }
            // Mon–Sat evening
            if ($this->get('mon_sat_evening_on', '1') === '1') {
                $normalEnd  = $this->get('mon_sat_evening_end', '20:30');
                $extendEnd  = $this->get('extended_evening_end', '23:30');
                $slots = array_merge($slots, $this->buildSlots(
                    $this->get('mon_sat_evening_start', '16:30'),
                    $extended ? $extendEnd : $normalEnd,
                    $duration
                ));
            }
        }
        return $slots;
    }

    private function buildSlots($start, $end, $duration) {
        $slots = [];
        $current = strtotime($start);
        $endTs   = strtotime($end);
        while ($current < $endTs) {
            $slots[] = date('H:i', $current);
            $current += $duration * 60;
        }
        return $slots;
    }
}
