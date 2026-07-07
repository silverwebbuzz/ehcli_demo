<?php
/**
 * Sync Controller
 * Single entry point for records queued offline and replayed by the service
 * worker / offline client. Deduplicates by the client-generated UUID so a
 * resubmission never creates a duplicate row.
 */

namespace App\Controllers;

use App\Models\ProgressReport;

class SyncController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param array $body Decoded JSON: { client_uuid, entity, data }
     * @return array Response with an 'http' key for the status code.
     */
    public function handle(array $body): array {
        $uuid   = $body['client_uuid'] ?? null;
        $entity = $body['entity'] ?? null;
        $data   = $body['data'] ?? [];

        if (!$uuid || !$entity || !is_array($data)) {
            return ['success' => false, 'message' => 'Bad sync payload', 'http' => 400];
        }

        switch ($entity) {
            case 'report':
                return $this->syncReport($uuid, $data);
            default:
                return ['success' => false, 'message' => "Unknown entity: {$entity}", 'http' => 400];
        }
    }

    /**
     * Create OR update a patient visit / progress report.
     * Creates are idempotent on client_uuid; updates (when a report_id is present)
     * are last-write-wins, so replaying a queued edit is always safe.
     */
    private function syncReport(string $uuid, array $data): array {
        $reportId  = $data['report_id'] ?? null;
        $patientId = $data['p_id'] ?? null;

        try {
            $patientController = new PatientController($this->db);

            // Edit of an existing visit — apply the update. Idempotent by nature,
            // so a retried edit simply re-writes the same fields.
            if (!empty($reportId)) {
                $result = $patientController->updateReport((int)$reportId, $data);
                if (!empty($result['success'])) {
                    $result['report_id'] = (int)$reportId;
                    $result['server_id'] = (int)$reportId;
                    $result['http'] = 200;
                    return $result;
                }
                // Validation / business-rule rejection — permanent, don't retry.
                $result['http'] = 422;
                return $result;
            }

            if (empty($patientId)) {
                return ['success' => false, 'message' => 'Missing patient id', 'http' => 400];
            }

            // Already stored under this UUID? Report success without duplicating.
            $reportModel = new ProgressReport($this->db);
            $existingId  = $reportModel->findByClientUuid($uuid);
            if ($existingId) {
                return ['success' => true, 'duplicate' => true,
                        'report_id' => $existingId, 'server_id' => $existingId, 'http' => 409];
            }

            // Reuse the normal create path (handles medicine master upsert too).
            $data['client_uuid'] = $uuid;
            $result = $patientController->addReport($patientId, $data);

            if (!empty($result['success'])) {
                $result['server_id'] = $result['report_id'] ?? null;
                $result['http'] = 200;
                return $result;
            }

            // Business-rule rejection (e.g. patient not found) — permanent, 4xx.
            $result['http'] = 422;
            return $result;

        } catch (\Exception $e) {
            error_log('[sync] report ' . $uuid . ': ' . $e->getMessage());
            // Transient/server error → client keeps it pending and retries.
            return ['success' => false, 'message' => $e->getMessage(), 'http' => 500];
        }
    }
}
