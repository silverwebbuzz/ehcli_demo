<?php
/**
 * Intake Controller
 * Homeopathy intake questionnaire: create shareable links, accept public
 * submissions (no login), and surface the scored case sheet to doctors.
 */

namespace App\Controllers;

use App\Models\HomeoIntake;
use App\Models\Patient;

class IntakeController {
    private $intakeModel;
    private $patientModel;

    public function __construct($db) {
        $this->intakeModel = new HomeoIntake($db);
        $this->patientModel = new Patient($db);
    }

    /**
     * Create (or reuse) an intake link for a patient and return its public URL.
     * If an unsubmitted, unexpired link already exists, reuse it.
     */
    public function createLink(int $patientId, ?int $createdBy): array {
        try {
            foreach ($this->intakeModel->findByPatient($patientId) as $existing) {
                if (!HomeoIntake::isExpired($existing)) {
                    return $this->linkResponse($existing['id'], $existing['token']);
                }
            }
            $created = $this->intakeModel->createLink($patientId, $createdBy);
            return $this->linkResponse($created['id'], $created['token']);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Could not create intake link: ' . $e->getMessage()];
        }
    }

    private function linkResponse(int $id, string $token): array {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return [
            'success' => true,
            'id'      => $id,
            'token'   => $token,
            'url'     => "{$scheme}://{$host}/intake/{$token}",
        ];
    }

    /** Public: load an intake by token for filling. Returns [row|null, error]. */
    public function loadPublic(string $token): array {
        $row = $this->intakeModel->findByToken($token);
        if (!$row) {
            return [null, 'This intake link is invalid.'];
        }
        if (HomeoIntake::isExpired($row)) {
            $msg = in_array($row['status'], ['submitted', 'locked'], true)
                ? 'This questionnaire has already been submitted. Thank you!'
                : 'This intake link has expired. Please contact the clinic for a new one.';
            return [$row, $msg];
        }
        // attach patient name (greeting) if linked
        if (!empty($row['patient_id'])) {
            $p = $this->patientModel->getById($row['patient_id']);
            $row['patient_name'] = $p ? trim(($p['fname'] ?? '') . ' ' . ($p['lname'] ?? '')) : '';
            $row['patient_gender'] = $p['gender'] ?? '';
        }
        return [$row, null];
    }

    /** Public: accept a submission for the given token. */
    public function submitPublic(string $token, array $post): array {
        try {
            $row = $this->intakeModel->findByToken($token);
            if (!$row) {
                return ['success' => false, 'message' => 'Invalid link.'];
            }
            if (HomeoIntake::isExpired($row)) {
                return ['success' => false, 'message' => 'This link is no longer accepting responses.'];
            }

            $answers = $this->sanitizeAnswers($post);
            $err = $this->validateRequired($answers);
            if ($err) {
                return ['success' => false, 'message' => $err];
            }

            $this->intakeModel->submit((int)$row['id'], $answers);
            return ['success' => true, 'message' => 'Thank you! Your responses have been submitted.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Could not save your answers: ' . $e->getMessage()];
        }
    }

    /** Keep only fields defined in the schema; trim scalars. */
    private function sanitizeAnswers(array $post): array {
        $allowed = [];
        foreach (HomeoIntake::schema()['tabs'] as $tab) {
            foreach ($tab['fields'] as $f) {
                $allowed[$f['name']] = $f;
            }
        }
        $clean = [];
        foreach ($post as $k => $v) {
            if (!isset($allowed[$k])) continue;
            if (is_array($v)) {
                $clean[$k] = array_values(array_filter(array_map('trim', $v), fn($x) => $x !== ''));
            } else {
                $t = trim((string)$v);
                if ($t !== '') $clean[$k] = $t;
            }
        }
        return $clean;
    }

    private function validateRequired(array $answers): ?string {
        foreach (HomeoIntake::schema()['tabs'] as $tab) {
            foreach ($tab['fields'] as $f) {
                if (!empty($f['required']) && empty($answers[$f['name']])) {
                    return 'Please fill the required field: ' . $f['label'];
                }
            }
        }
        return null;
    }

    /** Doctor: fetch a decoded, scored intake by id (for the result view). */
    public function getResult(int $id): ?array {
        $row = $this->intakeModel->getById($id);
        if (!$row) return null;
        $row = HomeoIntake::decode($row);
        if (!empty($row['patient_id'])) {
            $p = $this->patientModel->getById($row['patient_id']);
            $row['patient_name'] = $p ? trim(($p['fname'] ?? '') . ' ' . ($p['lname'] ?? '')) : '';
            $row['patient_meta'] = $p ? trim(($p['age'] ?? '') . ($p['gender'] ? ' / ' . ucfirst($p['gender']) : '')) : '';
        }
        return $row;
    }

    /** Latest intake for a patient, decoded (or null). Used to route the button. */
    public function latestForPatient(int $patientId): ?array {
        $rows = $this->intakeModel->findByPatient($patientId);
        return $rows ? HomeoIntake::decode($rows[0]) : null;
    }
}
