<?php
/**
 * Additional Info Model
 * Handles detailed patient assessment and medical history
 */

namespace App\Models;

use PDO;

class AdditionalInfo extends BaseModel {
    protected $table = 'additional_info';

    /**
     * Get additional info for a patient
     */
    public function getByPatientId($patientId) {
        $sql = "SELECT * FROM {$this->table} WHERE p_id = ?";
        $stmt = $this->query($sql, [$patientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create or update additional info for patient
     */
    public function saveForPatient($patientId, $data) {
        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }

        // Check if record exists
        $existing = $this->getByPatientId($patientId);

        $data['p_id'] = $patientId;

        if ($existing) {
            // Update existing
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            // Insert new
            return $this->insert($data);
        }
    }

    /**
     * Get health summary for patient
     */
    public function getHealthSummary($patientId) {
        $info = $this->getByPatientId($patientId);

        if (!$info) {
            return null;
        }

        // Return only significant health fields
        return [
            'chief_complaint' => $info['causation'] ?? null,
            'appetite' => $info['appetite'] ?? null,
            'sleep' => $info['sleep'] ?? null,
            'temperature' => $info['Temp'] ?? null,
            'blood_pressure' => $info['bp'] ?? null,
            'weight' => $info['wt'] ?? null,
            'height' => $info['Ht'] ?? null,
            'pulse' => $info['pulse'] ?? null,
            'respiration' => $info['rsp'] ?? null,
        ];
    }

    /**
     * Get family history
     */
    public function getFamilyHistory($patientId) {
        $info = $this->getByPatientId($patientId);

        if (!$info) {
            return null;
        }

        // Extract family history fields
        return [
            'father_health' => $info['f_male'] ?? null,
            'mother_health' => $info['f_female'] ?? null,
            'siblings_health' => $info['f_sibling'] ?? null,
            'diabetes' => $info['f_dibetes'] ?? null,
            'heart_disease' => $info['f_heartdis_bp'] ?? null,
            'tuberculosis' => $info['f_tb'] ?? null,
            'cancer' => $info['f_cancer'] ?? null,
            'asthma' => $info['f_asthuma'] ?? null,
            'epilepsy' => $info['f_epilepsy'] ?? null,
        ];
    }

    /**
     * Get past medical history
     */
    public function getPastMedicalHistory($patientId) {
        $info = $this->getByPatientId($patientId);

        if (!$info) {
            return null;
        }

        // Extract past medical history fields
        return [
            'typhoid' => $info['typhoid'] ?? null,
            'malaria' => $info['malaria'] ?? null,
            'tuberculosis' => $info['ptb'] ?? null,
            'jaundice' => $info['jaundice'] ?? null,
            'pneumonia' => $info['pneumonia'] ?? null,
            'measles' => $info['measles'] ?? null,
            'vaccinations' => $info['vaccination'] ?? null,
            'injuries' => $info['pinjury'] ?? null,
            'operations' => $info['operation'] ?? null,
        ];
    }
}
?>