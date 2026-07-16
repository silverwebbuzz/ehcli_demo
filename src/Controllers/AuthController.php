<?php
namespace App\Controllers;

use App\Models\User;

class AuthController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
    }

    // ── Login / Logout ────────────────────────────────────────────────────────

    public function showLogin() { return 'login'; }

    public function login($username, $password) {
        if (empty($username) || empty($password))
            return ['success' => false, 'message' => 'Username and password are required'];

        $user = $this->userModel->validateLogin($username, $password);

        if (!$user) {
            // Differentiate inactive vs wrong password for better UX
            $exists = $this->userModel->getByUsername($username);
            if ($exists && isset($exists['is_active']) && (int)$exists['is_active'] === 0)
                return ['success' => false, 'message' => 'Your account has been deactivated. Contact the doctor.'];
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        // Prevent session fixation: issue a fresh session ID now that the
        // identity has changed, discarding any pre-login (attacker-set) ID.
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['fullname']  = User::getFullName($user);
        // Fall back to 'doctor' if role column doesn't exist yet or is null/empty
        $_SESSION['role']      = (isset($user['role']) && $user['role'] !== '') ? $user['role'] : 'doctor';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time']= time();

        // Asst. Doctor is scoped to the Appointments workflow — land there, not the dashboard
        $landing = ($_SESSION['role'] === 'asst_doctor') ? '/queue' : '/dashboard';
        return ['success' => true, 'message' => 'Login successful', 'redirect' => $landing];
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'redirect' => '/login'];
    }

    // ── Session helpers ───────────────────────────────────────────────────────

    public static function isLoggedIn(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function getRole(): string {
        $r = $_SESSION['role'] ?? '';
        return ($r !== '') ? $r : 'doctor';
    }

    /** Returns true if current user has at least the given role level */
    public static function hasRole(string ...$roles): bool {
        return in_array(self::getRole(), $roles, true);
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email'    => $_SESSION['email'],
            'fullname' => $_SESSION['fullname'],
            'role'     => $_SESSION['role'] ?? 'doctor',
        ];
    }

    // ── Guards ────────────────────────────────────────────────────────────────

    /** Redirect to login if not authenticated */
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require one of the given roles.
     * If the user is logged in but lacks the role → show Access Denied page.
     * If not logged in at all → redirect to login.
     */
    public static function requireRole(string ...$roles): void {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            $roleName = User::roleLabel(self::getRole());
            require __DIR__ . '/../../views/error/403.php';
            exit;
        }
    }

    /**
     * Asst. Doctor is restricted to the Appointments page and the consultation
     * workflow it leads into (calling a patient → patient detail → visit report).
     * Every other route is denied. Call this once, early in routing.
     */
    public static function enforceAsstDoctorScope(string $route): void {
        if (!self::isLoggedIn() || self::getRole() !== 'asst_doctor') return;

        $allowed = [
            '#^login$#',
            '#^logout$#',
            '#^queue$#',                       // Appointments page
            '#^walkin$#',                      // Book walk-in from Appointments
            '#^patient/\d+$#',                 // Patient detail (consultation)
            '#^api/appointment/\d+/status$#',  // Call / finish / status changes
            '#^api/appointment/walkin$#',
            '#^api/slots$#',                   // Walk-in slot picker
            '#^api/medicines$#',               // Medicine search in visit report
            '#^api/patient/search$#',          // Patient lookup in walk-in form
            '#^api/patient/\d+/report$#',      // Save visit report
            '#^api/sync$#',                    // Offline sync replay (visit reports)
            '#^api/patient/\d+/update$#',      // Edit patient demographics
            '#^api/report/\d+/update$#',       // Edit visit report
            '#^api/report/\d+/payment$#',      // Record payment
            '#^invoice/\d+$#',                 // Print invoice
            '#^intake/patient/\d+$#',          // Fill homeopathy intake in-clinic
            '#^intake/\d+/result$#',           // View intake case sheet / score
            '#^api/intake/\d+/create$#',       // Generate patient intake link
            '#^intake/[a-f0-9]{40}$#',         // Open a tokenized intake link
            '#^api/intake/[a-f0-9]{40}/submit$#', // Submit tokenized intake
        ];
        foreach ($allowed as $pattern) {
            if (preg_match($pattern, $route)) return;
        }

        http_response_code(403);
        $roleName = User::roleLabel(self::getRole());
        require __DIR__ . '/../../views/error/403.php';
        exit;
    }

    public static function checkSessionTimeout(): void {
        if (!isset($_SESSION['login_time'])) return;
        $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600;
        if (time() - $_SESSION['login_time'] > $timeout) {
            session_unset();
            session_destroy();
            header('Location: /login?expired=1');
            exit;
        }
        $_SESSION['login_time'] = time();
    }
}
