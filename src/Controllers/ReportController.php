<?php
namespace App\Controllers;

use App\Models\Report;

class ReportController {
    private $model;

    public function __construct($db) {
        $this->model = new Report($db);
    }

    private function resolveDates(array $params): array {
        $period = $params['period'] ?? 'week';
        $year   = (int)($params['year'] ?? date('Y'));
        if ($period === 'custom') {
            $from = $params['from'] ?? date('Y-m-d');
            $to   = $params['to']   ?? date('Y-m-d');
            if ($from > $to) [$from, $to] = [$to, $from];
        } else {
            [$from, $to] = Report::periodDates($period, $year);
        }
        return [$period, $from, $to];
    }

    public function income(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        // For year selector: explicit param wins; otherwise derive from the resolved from-date
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'  => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'summary' => $this->model->incomeSummary($from, $to),
            'byDay'   => $this->model->incomeByDay($from, $to),
            'byWeek'  => $this->model->incomeByWeek($from, $to),
            'byMonth' => $this->model->incomeByMonth($year),
        ];
    }

    public function patients(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'       => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'byDay'        => $this->model->newPatientsByDay($from, $to),
            'byWeek'       => $this->model->newPatientsByWeek($from, $to),
            'byMonth'      => $this->model->newPatientsByMonth($year),
            'gender'       => $this->model->genderSplit(),
            'ageGroups'    => $this->model->ageGroups(),
            'complaints'   => $this->model->topComplaints(10),
            'newReturning' => $this->model->newVsReturning($from, $to),
        ];
    }

    public function queueOps(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'      => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'byDay'       => $this->model->appointmentsByDay($from, $to),
            'byWeek'      => $this->model->appointmentsByWeek($from, $to),
            'byMonth'     => $this->model->appointmentsByMonth($year),
            'consultTime' => $this->model->avgConsultTime($from, $to),
            'busyDays'    => $this->model->busyDays($from, $to),
            'busySlots'   => $this->model->busySlots($from, $to),
            'noShow'      => $this->model->noShowRate($from, $to),
        ];
    }

    public function medicines(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'  => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'topMeds' => $this->model->topMedicines($from, $to, 15),
            'byDay'   => $this->model->prescriptionsByDay($from, $to),
            'byWeek'  => $this->model->prescriptionsByWeek($from, $to),
            'byMonth' => $this->model->prescriptionsByMonth($year),
        ];
    }

    public function productivity(array $params = []): array {
        [$period, $from, $to] = $this->resolveDates($params);
        $year = isset($params['year']) && (int)$params['year'] > 0
            ? (int)$params['year']
            : (int)date('Y', strtotime($from));

        return [
            'period'       => $period, 'from' => $from, 'to' => $to, 'year' => $year,
            'summary'      => $this->model->productivitySummary($from, $to),
            'byDay'        => $this->model->patientsSeen($from, $to),
            'byWeek'       => $this->model->patientsSeenByWeek($from, $to),
            'byMonth'      => $this->model->patientsSeenByMonth($year),
            'consultTrend' => $this->model->consultTimeTrend($from, $to),
            'busyDays'     => $this->model->busyDays($from, $to),
        ];
    }
}
