<?php
namespace App\Models;

use PDO;

class User extends BaseModel {
    protected $table = 'user';

    const ROLES = [
        'doctor'      => 'Doctor',
        'asst_doctor' => 'Asst. Doctor',
        'reception'   => 'Reception',
    ];

    // ── Lookups ───────────────────────────────────────────────────────────────

    public function getByEmail($email) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUsername($username) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE username = ?", [$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** All users ordered by role then name */
    public function getAll($limit = null, $offset = 0) {
        $stmt = $this->query(
            "SELECT id, fname, mname, lname, username, email, contact_no, role, is_active
             FROM {$this->table}
             ORDER BY FIELD(role,'doctor','asst_doctor','reception'), fname"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function validateLogin($username, $password) {
        $user = $this->getByUsername($username);
        if (!$user) return false;

        // Block inactive accounts
        if (isset($user['is_active']) && (int)$user['is_active'] === 0) return false;

        // Plain-text legacy check first, then bcrypt
        $ok = ($password === $user['password'])
            || password_verify($password, $user['password']);

        if (!$ok) return false;

        unset($user['password']);
        return $user;
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function create($data) {
        $required = ['fname', 'username', 'email', 'password', 'contact_no'];
        foreach ($required as $f) {
            if (empty($data[$f] ?? null))
                throw new \Exception("Field '{$f}' is required");
        }

        if ($this->getByUsername($data['username']))
            throw new \Exception("Username '{$data['username']}' is already taken");

        if ($this->getByEmail($data['email']))
            throw new \Exception("Email '{$data['email']}' is already registered");

        if (!array_key_exists($data['role'] ?? '', self::ROLES))
            throw new \Exception("Invalid role");

        $insert = [
            'fname'      => $data['fname'],
            'mname'      => $data['mname'] ?? '',
            'lname'      => $data['lname'] ?? '',
            'username'   => $data['username'],
            'email'      => $data['email'],
            'contact_no' => $data['contact_no'],
            'role'       => $data['role'],
            'is_active'  => 1,
            'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            'dob'        => $data['dob']     ?? date('Y-m-d'),
            'gender'     => $data['gender']  ?? 'M',
            'address'    => $data['address'] ?? '',
            'city'       => $data['city']    ?? '',
            'state'      => $data['state']   ?? '',
            'country'    => $data['country'] ?? '',
            'zip'        => $data['zip']     ?? '',
        ];

        return $this->insert($insert);
    }

    public function updateUser($id, $data, $currentUserId) {
        $clean = [];

        foreach (['fname','mname','lname','email','contact_no'] as $f) {
            if (isset($data[$f])) $clean[$f] = trim($data[$f]);
        }

        // Role — doctor cannot demote themselves
        if (isset($data['role']) && (int)$id !== (int)$currentUserId) {
            if (!array_key_exists($data['role'], self::ROLES))
                throw new \Exception("Invalid role");
            $clean['role'] = $data['role'];
        }

        // Active status — same guard
        if (isset($data['is_active']) && (int)$id !== (int)$currentUserId) {
            $clean['is_active'] = (int)(bool)$data['is_active'];
        }

        // Password change (optional)
        if (!empty($data['new_password'])) {
            if (strlen($data['new_password']) < 6)
                throw new \Exception("Password must be at least 6 characters");
            $clean['password'] = password_hash($data['new_password'], PASSWORD_BCRYPT);
        }

        if (empty($clean)) throw new \Exception("Nothing to update");

        $this->update($id, $clean);
        return true;
    }

    public function deleteUser($id, $currentUserId) {
        if ((int)$id === (int)$currentUserId)
            throw new \Exception("You cannot delete your own account");
        $this->delete($id);
        return true;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function getFullName($user) {
        return trim("{$user['fname']} " . ($user['mname'] ? "{$user['mname']} " : '') . "{$user['lname']}");
    }

    public static function roleLabel($role) {
        return self::ROLES[$role] ?? ucfirst($role);
    }
}
