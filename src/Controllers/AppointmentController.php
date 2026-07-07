<?php
namespace App\Controllers;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\Patient;

class AppointmentController {
    private $apptModel;
    private $settingModel;
    private $patientModel;

    public function __construct($db) {
        $this->apptModel    = new Appointment($db);
        $this->settingModel = new Setting($db);
        $this->patientModel = new Patient($db);
    }

    /**
     * Normalise a phone number for storage: keep digits only (plus an optional
     * leading +) and cap the length so it never overflows the phone column.
     * Strips spaces, dashes and brackets that can push a normal number past the
     * column limit and trigger "Data too long for column 'patient_phone'".
     */
    private static function cleanPhone($phone) {
        $phone = trim((string)$phone);
        if ($phone === '') return '';
        $plus  = ($phone[0] === '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $phone);
        return substr($plus . $digits, 0, 15);
    }

    /** Appointments page data — supports today / week / month views */
    public function getQueue($date = null, $view = 'today') {
        $today = date('Y-m-d');
        if ($view === 'week') {
            $from  = date('Y-m-d', strtotime('monday this week'));
            $to    = date('Y-m-d', strtotime('sunday this week'));
            $queue = $this->apptModel->getByRange($from, $to);
            $stats = $this->apptModel->rangeStats($from, $to);
            return ['success'=>true, 'queue'=>$queue, 'stats'=>$stats, 'date'=>$today, 'view'=>'week', 'from'=>$from, 'to'=>$to];
        }
        if ($view === 'month') {
            $from  = date('Y-m-01');
            $to    = date('Y-m-t');
            $queue = $this->apptModel->getByRange($from, $to);
            $stats = $this->apptModel->rangeStats($from, $to);
            return ['success'=>true, 'queue'=>$queue, 'stats'=>$stats, 'date'=>$today, 'view'=>'month', 'from'=>$from, 'to'=>$to];
        }
        // Default: today (also handles explicit date nav)
        $date  = $date ?? $today;
        $queue = $this->apptModel->getByDate($date);
        $stats = $this->apptModel->todayStats($date);
        return ['success'=>true, 'queue'=>$queue, 'stats'=>$stats, 'date'=>$date, 'view'=>'today', 'from'=>$date, 'to'=>$date];
    }

    /** Create walk-in token (receptionist) */
    public function createWalkin($data, $userId = null) {
        try {
            if (!empty($data['patient_id'])) {
                // Existing registered patient
                $patient = $this->patientModel->getById($data['patient_id']);
                if (!$patient) return ['success' => false, 'message' => 'Patient not found'];
                $data['patient_name']  = trim(($patient['fname'] ?? '') . ' ' . ($patient['lname'] ?? ''));
                $data['patient_phone'] = self::cleanPhone($patient['contact_no'] ?? '');
                $data['is_new_patient'] = 0;
            } else {
                // New patient — auto-create a record so the doctor can open the detail page.
                $phone = self::cleanPhone($data['patient_phone'] ?? '');
                $data['patient_phone'] = $phone;
                $chief = trim($data['chief_complaint'] ?? '');

                if (!empty(trim($data['fname'] ?? ''))) {
                    // Full "New Patient" registration — all demographic fields provided.
                    $pdata = array_intersect_key($data, array_flip([
                        'fname','lname','dob','age','gender','mrg_status','veg','religion',
                        'refered_by','occupation','education','address','city','state','zip',
                    ]));
                    $pdata['contact_no'] = $phone;
                    if ($chief !== '') $pdata['chief'] = $chief;
                    $newId = $this->patientModel->create($pdata);
                    $data['patient_id']     = $newId;
                    $data['patient_name']   = trim(($pdata['fname'] ?? '') . ' ' . ($pdata['lname'] ?? ''));
                    $data['is_new_patient'] = 1;
                } else {
                    // Quick unregistered walk-in — only a name (+ optional age/gender).
                    $name = trim($data['patient_name'] ?? '');
                    if ($name) {
                        $newId = $this->patientModel->createQuick($name, $phone, $chief, [
                            'age'    => $data['patient_age']    ?? '',
                            'gender' => $data['patient_gender'] ?? '',
                        ]);
                        $data['patient_id']     = $newId;
                    }
                    $data['is_new_patient'] = 1;
                }
            }
            // chief_complaint is optional — set null if empty
            if (empty(trim($data['chief_complaint'] ?? ''))) {
                $data['chief_complaint'] = null;
            }

            $data['patient_phone'] = self::cleanPhone($data['patient_phone'] ?? '');
            $id   = $this->apptModel->createWalkin($data, $userId);
            $appt = $this->apptModel->getById($id);
            return [
                'success'    => true,
                'message'    => 'Token created',
                'token'      => $appt['token_number'],
                'id'         => $id,
                'patient_id' => $data['patient_id'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Update appointment status (AJAX) */
    public function updateStatus($id, $status) {
        $allowed = ['waiting', 'arrived', 'in_consultation', 'completed', 'cancelled', 'no_show'];
        if (!in_array($status, $allowed)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        try {
            $this->apptModel->updateStatus($id, $status);
            $appt = $this->apptModel->getByIdFull($id);

            $redirect = null;
            if ($status === 'in_consultation') {
                if (!empty($appt['patient_id'])) {
                    $redirect = '/patient/' . (int)$appt['patient_id'] . '?from=queue&appt=' . (int)$id;
                } else {
                    $redirect = '/queue';
                }
            }
            if ($status === 'completed') {
                $redirect = '/queue';
            }

            return ['success' => true, 'status' => $status, 'redirect' => $redirect];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Get available slots for public booking page */
    public function getAvailableSlots($date, $extended = false) {
        try {
            // Check if clinic is closed that day
            $closed = $this->apptModel->isClosedDate($date);
            if ($closed) {
                return ['success' => true, 'slots' => [], 'closed' => true, 'date' => $date];
            }

            $allSlots    = $this->settingModel->generateSlots($date, $extended);
            $bookedSlots = $this->apptModel->bookedSlots($date);
            $maxPerSlot  = max(1, (int)$this->settingModel->get('max_per_slot', 1));

            // Count active bookings per slot
            $bookedCount = array_count_values($bookedSlots);

            $result = [];
            foreach ($allSlots as $slot) {
                $booked = $bookedCount[$slot] ?? 0;
                $result[] = [
                    'time'      => $slot,
                    'available' => $booked < $maxPerSlot,
                    'booked'    => $booked,
                    'max'       => $maxPerSlot,
                ];
            }
            return ['success' => true, 'slots' => $result, 'date' => $date, 'max_per_slot' => $maxPerSlot];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Public booking — lookup patient by phone */
    public function lookupByPhone($phone) {
        try {
            $patient = $this->patientModel->findByPhone($phone);
            if ($patient) {
                return ['success' => true, 'found' => true, 'patient' => $patient];
            }
            return ['success' => true, 'found' => false];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Create pre-booked appointment (public page) */
    public function createPrebooked($data) {
        try {
            // Validate slot still available
            $slots = $this->getAvailableSlots($data['appt_date']);
            $slotAvailable = false;
            foreach ($slots['slots'] ?? [] as $s) {
                if ($s['time'] === $data['slot_time'] && $s['available']) {
                    $slotAvailable = true; break;
                }
            }
            if (!$slotAvailable) {
                return ['success' => false, 'message' => 'This slot is no longer available'];
            }

            if (!empty($data['patient_id'])) {
                // Existing registered patient
                $patient = $this->patientModel->getById($data['patient_id']);
                if ($patient) {
                    $data['patient_name']   = trim(($patient['fname'] ?? '') . ' ' . ($patient['lname'] ?? ''));
                    $data['patient_phone']  = self::cleanPhone($patient['contact_no'] ?? '');
                    $data['is_new_patient'] = 0;
                }
            } else {
                // New patient — auto-create basic record
                $name  = trim($data['patient_name'] ?? '');
                $phone = self::cleanPhone($data['patient_phone'] ?? '');
                $data['patient_phone'] = $phone;
                $chief = trim($data['chief_complaint'] ?? '');
                if ($name) {
                    $newId = $this->patientModel->createQuick($name, $phone, $chief);
                    $data['patient_id']     = $newId;
                    $data['is_new_patient'] = 1;
                } else {
                    $data['is_new_patient'] = 1;
                }
            }
            // chief_complaint is optional
            if (empty(trim($data['chief_complaint'] ?? ''))) {
                $data['chief_complaint'] = null;
            }

            $data['patient_phone'] = self::cleanPhone($data['patient_phone'] ?? '');
            $id   = $this->apptModel->createPrebooked($data);
            $appt = $this->apptModel->getById($id);
            return [
                'success'      => true,
                'message'      => 'Appointment booked successfully',
                'token'        => $appt['token_number'],
                'id'           => $id,
                'slot_time'    => $data['slot_time'],
                'appt_date'    => $data['appt_date'],
                'patient_id'   => $data['patient_id'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Settings page save */
    public function saveSettings($data) {
        try {
            $allowed = [
                'slot_duration_min',
                'mon_sat_morning_on','mon_sat_morning_start','mon_sat_morning_end',
                'mon_sat_evening_on','mon_sat_evening_start','mon_sat_evening_end',
                'sunday_on','sunday_start','sunday_end',
                'max_per_slot','clinic_name','clinic_phone','consultation_fee',
                'booking_days_ahead',
                'extended_morning_end','extended_evening_end',
                // Invoice settings
                'inv_doctor_name','inv_qualification','inv_phone','inv_email','inv_address',
                'inv_show_pan','inv_pan',
                'inv_gst_enabled','inv_gst_number','inv_gst_rate',
            ];
            $clean = array_intersect_key($data, array_flip($allowed));
            // Checkboxes not sent when off — set to 0
            foreach (['mon_sat_morning_on','mon_sat_evening_on','sunday_on','inv_show_pan','inv_gst_enabled'] as $cb) {
                if (!isset($clean[$cb])) $clean[$cb] = '0';
            }
            $this->settingModel->setMany($clean);
            return ['success' => true, 'message' => 'Settings saved'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
