<?php
/**
 * Medicine Controller
 */

namespace App\Controllers;

use App\Models\Medicine;

class MedicineController {
    private $medicineModel;

    public function __construct($db) {
        $this->medicineModel = new Medicine($db);
    }

    public function search($query) {
        try {
            $results = $this->medicineModel->search($query);
            return ['success' => true, 'data' => $results];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTop() {
        try {
            $results = $this->medicineModel->getTop(60);
            return ['success' => true, 'data' => $results];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
