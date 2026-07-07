<?php
/**
 * Dr. Feelgood - Main Entry Point
 *
 * This file is the entry point for the application.
 * Place this file in the root directory: /home/silverwebbuzz_in/public_html/drfeelgoods.in/app/index.php
 */

// Enable error reporting
error_reporting(E_ALL);

// Load configuration first to check DEBUG_MODE
require_once __DIR__ . '/config/constants.php';

// Configure error handling based on debug mode
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1); // Display errors to user in debug mode
} else {
    ini_set('display_errors', 0); // Don't display errors to user in production
}
ini_set('log_errors', 1); // Always log errors

// ── Session hardening ──────────────────────────────────────────────────────
// Keep users logged in for the full SESSION_TIMEOUT window. On shared hosting
// the global session GC (and a short session.gc_maxlifetime) can wipe session
// files after only a few minutes — the usual cause of "logged out automatically
// after a few minutes". Using a private save path + a matching gc lifetime and
// cookie lifetime keeps the session alive for the intended duration.
$sessionLifetime = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600;
$sessionPath = __DIR__ . '/db/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0700, true);
    // Block web access to session files if this dir ever sits under the docroot
    @file_put_contents($sessionPath . '/.htaccess', "Require all denied\nDeny from all\n");
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}
// Extend server-side session lifetime so the session file isn't garbage-collected
// early. Give the cookie the same lifetime so the login survives browser restarts
// for the full day; the sliding SESSION_TIMEOUT check in AuthController still
// handles inactivity logout.
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Start session
session_start();

// Indian Standard Time for all date() calls
date_default_timezone_set('Asia/Kolkata');

// Load database configuration
require_once __DIR__ . '/config/database.php';

// Autoloader for classes
spl_autoload_register(function($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Autoloader: File not found for class '{$class}' - Expected: '{$file}'");
    }
});

// Initialize database connection
try {
    $database = new Database();
    $db = $database->connect();

    if (!$db) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
} catch (\Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Import controllers
use App\Controllers\AuthController;
use App\Controllers\PatientController;
use App\Controllers\MedicineController;
use App\Controllers\AppointmentController;
use App\Controllers\ReportController;

// Check session timeout
AuthController::checkSessionTimeout();

// Get request path from REQUEST_URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Extract route - remove /app if it exists, otherwise use as-is
if (strpos($request_uri, '/app/') === 0) {
    $route = substr($request_uri, 5); // Remove '/app/'
} else {
    $route = $request_uri;
}

$route = trim($route, '/');

// Log route for debugging
error_log("Route extracted: '{$route}' from REQUEST_URI: '{$request_uri}' Method: {$_SERVER['REQUEST_METHOD']}");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . json_encode($_POST));
}

// Set default route — logged-in users land on the dashboard, others on login
if (empty($route)) {
    $route = AuthController::isLoggedIn() ? 'dashboard' : 'login';
}

// Asst. Doctor is locked to the Appointments + consultation workflow
AuthController::enforceAsstDoctorScope($route);

// Route handler
switch ($route) {
    // Authentication routes
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            error_log("Login attempt - Username: '{$username}', Password length: " . strlen($password));

            try {
                $authController = new AuthController($db);
                $response = $authController->login($username, $password);

                header('Content-Type: application/json');
                $json = json_encode($response);
                error_log("Login response JSON: " . $json);
                echo $json;
                exit;
            } catch (\Exception $e) {
                error_log("Login error: " . $e->getMessage());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
                exit;
            }
        }
        // Already logged in? Skip the login form and go straight to the dashboard.
        if (AuthController::isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/views/auth/login.php';
        break;

    case 'logout':
        $authController = new AuthController($db);
        $authController->logout();
        header('Location: /login');
        exit;

    // ── Help page (Doctor only) ───────────────────────────────────────────────

    case 'help':
        AuthController::requireRole('doctor');
        require __DIR__ . '/views/help.php';
        break;

    // ── User management (Doctor only) ─────────────────────────────────────────

    case 'users':
        AuthController::requireRole('doctor');
        $userModel = new App\Models\User($db);
        $users = $userModel->getAll();
        require __DIR__ . '/views/users/list.php';
        break;

    case 'api/users/create':
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        try {
            $userModel = new App\Models\User($db);
            $createData = $_POST;
            // Form sends 'new_password'; create() expects 'password'
            if (isset($createData['new_password']) && $createData['new_password'] !== '') {
                $createData['password'] = $createData['new_password'];
            }
            $newId = $userModel->create($createData);
            echo json_encode(['success'=>true,'message'=>'User created successfully','id'=>$newId]);
        } catch (\Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;

    case (preg_match('/^api\/users\/(\d+)\/update$/', $route, $matches) ? true : false):
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        try {
            $userModel = new App\Models\User($db);
            $userModel->updateUser($matches[1], $_POST, $_SESSION['user_id']);
            echo json_encode(['success'=>true,'message'=>'User updated successfully']);
        } catch (\Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;

    case (preg_match('/^api\/users\/(\d+)\/delete$/', $route, $matches) ? true : false):
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        try {
            $userModel = new App\Models\User($db);
            $userModel->deleteUser($matches[1], $_SESSION['user_id']);
            echo json_encode(['success'=>true,'message'=>'User deleted']);
        } catch (\Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;

    // ── Patient routes ─────────────────────────────────────────────────────────

    case 'patients':
        AuthController::requireLogin();
        $patientModel = new \App\Models\Patient($db);
        $initialRows  = $patientModel->getPaginated(1, 10, '');
        $totalPatients = $patientModel->getTotalCount('');
        require __DIR__ . '/views/patient/list.php';
        break;

    case (preg_match('/^patient\/(\d+)$/', $route, $matches) ? true : false):
        AuthController::requireLogin();
        $patientId = $matches[1];
        $patientController = new PatientController($db);
        $response = $patientController->getDetail($patientId);
        require __DIR__ . '/views/patient/detail.php';
        break;

    case 'patient/create':
        AuthController::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patientController = new PatientController($db);
            $response = $patientController->create($_POST);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        require __DIR__ . '/views/patient/create.php';
        break;

    case 'api/patients':
        AuthController::requireLogin();
        header('Content-Type: application/json');
        $patientModel = new \App\Models\Patient($db);
        $page   = max(1, (int)($_GET['page']   ?? 1));
        $limit  = in_array((int)($_GET['limit'] ?? 25), [10,25,50,100]) ? (int)$_GET['limit'] : 25;
        $search = trim($_GET['search'] ?? '');
        $rows   = $patientModel->getPaginated($page, $limit, $search);
        $total  = $patientModel->getTotalCount($search);
        echo json_encode(['success'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'limit'=>$limit]);
        exit;

    case 'api/medicines':
        AuthController::requireLogin();
        header('Content-Type: application/json');
        $medicineController = new MedicineController($db);
        $q = trim($_GET['q'] ?? '');
        $response = $q !== '' ? $medicineController->search($q) : $medicineController->getTop();
        echo json_encode($response);
        exit;

    case 'api/patient/search':
        AuthController::requireLogin();
        $query = $_GET['q'] ?? '';
        $patientController = new PatientController($db);
        $response = $patientController->search($query);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    case (preg_match('/^api\/patient\/(\d+)\/report$/', $route, $matches) ? true : false):
        // Only doctor + asst_doctor can add visit notes
        AuthController::requireRole('doctor', 'asst_doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $patientId = $matches[1];
        $patientController = new PatientController($db);
        $response = $patientController->addReport($patientId, $_POST);
        echo json_encode($response);
        exit;

    // Offline sync endpoint — replays records queued in the browser (IndexedDB
    // outbox) by the service worker / offline client. Idempotent per client_uuid.
    case 'api/sync':
        AuthController::requireRole('doctor', 'asst_doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit;
        }
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $syncController = new \App\Controllers\SyncController($db);
        $response = $syncController->handle($body);
        http_response_code($response['http'] ?? 200);
        unset($response['http']);
        echo json_encode($response);
        exit;

    case (preg_match('/^api\/patient\/(\d+)\/update$/', $route, $matches) ? true : false):
        AuthController::requireLogin();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $patientId = $matches[1];
        $patientController = new PatientController($db);
        $response = $patientController->update($patientId, $_POST);
        echo json_encode($response);
        exit;

    case (preg_match('/^api\/report\/(\d+)\/update$/', $route, $matches) ? true : false):
        // Only doctor + asst_doctor can edit visit notes
        AuthController::requireRole('doctor', 'asst_doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $reportId = $matches[1];
        $patientController = new PatientController($db);
        $response = $patientController->updateReport($reportId, $_POST);
        echo json_encode($response);
        exit;

    case (preg_match('/^api\/report\/(\d+)\/payment$/', $route, $matches) ? true : false):
        // Reception and doctor can change payment status
        AuthController::requireRole('reception', 'doctor', 'asst_doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $reportId = $matches[1];
        $paymentStatus = $_POST['payment_status'] ?? '';
        if (!in_array($paymentStatus, ['paid', 'remaining'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment status']); exit;
        }
        // Payment method (cash/online) is optional — only applied when provided
        $paymentType = $_POST['payment_type'] ?? '';
        try {
            $progressReportModel = new App\Models\ProgressReport($db);
            $report = $progressReportModel->getById($reportId);
            if (!$report) {
                echo json_encode(['success' => false, 'message' => 'Report not found']); exit;
            }
            $update = ['payment_status' => $paymentStatus];
            if (in_array($paymentType, ['cash', 'online'])) {
                $update['payment_type'] = $paymentType;
            }
            $progressReportModel->updateReport($reportId, $update);
            echo json_encode([
                'success'        => true,
                'message'        => 'Payment status updated',
                'payment_status' => $paymentStatus,
                'payment_type'   => $update['payment_type'] ?? ($report['payment_type'] ?? ''),
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    case (preg_match('/^api\/patient\/(\d+)\/delete$/', $route, $matches) ? true : false):
        // Only the main doctor can permanently delete a patient + all records
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $patientId = $matches[1];
        $patientController = new PatientController($db);
        $response = $patientController->deletePatient($patientId);
        echo json_encode($response);
        exit;

    case 'dashboard':
        AuthController::requireLogin();
        $patientController  = new PatientController($db);
        $apptController     = new AppointmentController($db);
        $reportModel        = new App\Models\Report($db);
        $recentPatients     = $patientController->getRecent(10);
        $todayQueueData     = $apptController->getQueue(date('Y-m-d'));
        $visitedToday       = (new App\Models\ProgressReport($db))->getVisitedToday();
        $dashStats = [
            'total_patients'   => (int)(new App\Models\Patient($db))->getTotalCount(),
            'total_reports'    => (int)$reportModel->count(),
            'new_this_month'   => (int)count($reportModel->newPatientsByDay(date('Y-m-01'), date('Y-m-d'))),
            'completed_today'  => (int)($todayQueueData['stats']['completed'] ?? 0),
        ];
        require __DIR__ . '/views/dashboard.php';
        break;

    // ── Appointment / Queue routes ──────────────────────────────────────────

    case 'queue':
        AuthController::requireLogin();
        $apptController = new AppointmentController($db);
        $view = $_GET['view'] ?? 'today';
        $date = $_GET['date'] ?? null;
        $queueData = $apptController->getQueue($date, $view);
        require __DIR__ . '/views/appointment/queue.php';
        break;

    case 'walkin':
        AuthController::requireLogin();
        require __DIR__ . '/views/appointment/walkin.php';
        break;

    case 'clinic-settings':
        // Only doctor can access settings
        AuthController::requireRole('doctor');
        $apptController = new AppointmentController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = $apptController->saveSettings($_POST);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        $settingModel = new App\Models\Setting($db);
        $clinicSettings = $settingModel->getAllSettings();
        require __DIR__ . '/views/appointment/settings.php';
        break;

    case 'api/appointment/walkin':
        AuthController::requireLogin();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $apptController = new AppointmentController($db);
        $userId = $_SESSION['user_id'] ?? null;
        $response = $apptController->createWalkin($_POST, $userId);
        echo json_encode($response);
        exit;

    case (preg_match('/^api\/appointment\/(\d+)\/status$/', $route, $matches) ? true : false):
        AuthController::requireLogin();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $apptId     = $matches[1];
        $newStatus  = $_POST['status'] ?? '';
        $role       = AuthController::getRole();
        // Reception can mark arrived, undo to waiting, no_show, or cancel — but not call/finish
        if ($role === 'reception' && !in_array($newStatus, ['arrived', 'waiting', 'no_show', 'cancelled'])) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>'Your role cannot perform this action']);
            exit;
        }
        $apptController = new AppointmentController($db);
        $response = $apptController->updateStatus($apptId, $newStatus);
        echo json_encode($response);
        exit;

    case 'api/slots':
        header('Content-Type: application/json');
        $date = $_GET['date'] ?? date('Y-m-d');
        $extended = isset($_SESSION['user_id']) && ($_GET['extended'] ?? '0') === '1';
        $apptController = new AppointmentController($db);
        $response = $apptController->getAvailableSlots($date, $extended);
        echo json_encode($response);
        exit;

    case 'api/booking':
        // Public — no auth required
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }
        $apptController = new AppointmentController($db);
        $response = $apptController->createPrebooked($_POST);
        echo json_encode($response);
        exit;

    case 'api/patient/lookup':
        // Public — no auth required
        header('Content-Type: application/json');
        $phone = trim($_GET['phone'] ?? '');
        if ($phone === '') {
            echo json_encode(['success' => false, 'message' => 'Phone required']); exit;
        }
        $apptController = new AppointmentController($db);
        $response = $apptController->lookupByPhone($phone);
        echo json_encode($response);
        exit;

    case 'booking':
        // Public booking page — no auth required
        $settingModel     = new App\Models\Setting($db);
        $bookingDaysAhead = (int)$settingModel->get('booking_days_ahead', 15);
        $closedDatesRaw   = (new App\Models\Appointment($db))->getClosedDates();
        $closedDatesArr   = array_column($closedDatesRaw, 'date');
        $unavailableDates = $closedDatesArr;
        for ($i = 0; $i < $bookingDaysAhead; $i++) {
            $d   = date('Y-m-d', strtotime("+{$i} days"));
            $dow = (int)date('N', strtotime($d));
            $noSlots = false;
            if ($dow === 7) {
                $noSlots = $settingModel->get('sunday_on', '1') !== '1';
            } else {
                $morningOff = $settingModel->get('mon_sat_morning_on', '1') !== '1';
                $eveningOff = $settingModel->get('mon_sat_evening_on', '1') !== '1';
                $noSlots = $morningOff && $eveningOff;
            }
            if ($noSlots && !in_array($d, $unavailableDates)) $unavailableDates[] = $d;
        }
        require __DIR__ . '/views/booking/index.php';
        break;

    case 'api/closed-dates':
        AuthController::requireLogin();
        header('Content-Type: application/json');
        $dates = (new App\Models\Appointment($db))->getClosedDates();
        echo json_encode(['success' => true, 'dates' => $dates]);
        exit;

    case 'api/closed-dates/add':
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        $date   = $_POST['date']   ?? '';
        $reason = $_POST['reason'] ?? '';
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success'=>false,'message'=>'Invalid date']); exit;
        }
        (new App\Models\Appointment($db))->addClosedDate($date, $reason);
        echo json_encode(['success' => true]);
        exit;

    case 'api/closed-dates/remove':
        AuthController::requireRole('doctor');
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
        (new App\Models\Appointment($db))->removeClosedDate($id);
        echo json_encode(['success' => true]);
        exit;

    // ── Reports (doctor + asst_doctor only) ───────────────────────────────────

    case 'reports/income':
        AuthController::requireRole('doctor', 'asst_doctor');
        $reportController = new ReportController($db);
        $reportData = $reportController->income($_GET);
        require __DIR__ . '/views/reports/income.php';
        break;

    case 'reports/patients':
        AuthController::requireRole('doctor', 'asst_doctor');
        $reportController = new ReportController($db);
        $reportData = $reportController->patients($_GET);
        require __DIR__ . '/views/reports/patients.php';
        break;

    case 'reports/queue':
        AuthController::requireRole('doctor', 'asst_doctor');
        $reportController = new ReportController($db);
        $reportData = $reportController->queueOps($_GET);
        require __DIR__ . '/views/reports/queue.php';
        break;

    case 'reports/medicines':
        AuthController::requireRole('doctor', 'asst_doctor');
        $reportController = new ReportController($db);
        $reportData = $reportController->medicines($_GET);
        require __DIR__ . '/views/reports/medicines.php';
        break;

    case 'reports/productivity':
        AuthController::requireRole('doctor', 'asst_doctor');
        $reportController = new ReportController($db);
        $reportData = $reportController->productivity($_GET);
        require __DIR__ . '/views/reports/productivity.php';
        break;

    // ── Invoice (doctor + asst_doctor + reception) ────────────────────────────
    case (preg_match('/^invoice\/(\d+)$/', $route, $matches) ? true : false):
        AuthController::requireRole('doctor', 'asst_doctor', 'reception');
        $reportId = (int)$matches[1];
        $reportModel = new App\Models\Report($db);
        $report = $reportModel->getById($reportId);
        if (!$report) {
            http_response_code(404);
            require __DIR__ . '/views/error/404.php';
            break;
        }
        $patientController = new PatientController($db);
        $patientResp = $patientController->getDetail($report['p_id']);
        $patient = $patientResp['patient'] ?? null;
        $settingModel = new App\Models\Setting($db);
        $s = $settingModel->getAllSettings();
        require __DIR__ . '/views/invoice.php';
        break;

    default:
        error_log("404 - Route not found: '{$route}'");
        http_response_code(404);
        require __DIR__ . '/views/error/404.php';
        break;
}
?>
