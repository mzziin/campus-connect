<?php
// Authentication Functions
// Campus Connect - Session Helpers

require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is approved
 */
function is_approved() {
    return isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'approved';
}

/**
 * Check if current user is admin
 */
function is_admin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function get_user_data() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'status' => $_SESSION['user_status'] ?? null,
    ];
}

/**
 * User login
 */
function user_login($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['account_status'];
}

/**
 * Admin login
 */
function admin_login($admin) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
}

/**
 * Logout
 */
function logout() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Register a new user
 */
function register_user(array $data): array {
    $pdo = get_db();

    // Validate inputs
    if (empty($data['full_name']) || strlen($data['full_name']) < 2 || strlen($data['full_name']) > 150) {
        return ['success' => false, 'error' => 'Full name must be between 2 and 150 characters.'];
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    if (empty($data['password']) || strlen($data['password']) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered.'];
    }

    // Check college_id uniqueness if provided
    if (!empty($data['college_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE college_id = ?");
        $stmt->execute([$data['college_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'College ID already registered.'];
        }
    }

    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, phone, department, college_id, account_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $data['full_name'],
        $data['email'],
        $password_hash,
        !empty($data['phone']) ? $data['phone'] : null,
        !empty($data['department']) ? $data['department'] : null,
        !empty($data['college_id']) ? $data['college_id'] : null
    ]);

    return ['success' => true];
}

/**
 * Login user
 */
function login_user(string $email, string $password): array {
    $pdo = get_db();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Verify password FIRST — before checking status
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Check account status
    if ($user['account_status'] === 'rejected') {
        return ['success' => false, 'error' => 'Your account has been rejected. Contact admin for more information.'];
    }

    if ($user['account_status'] === 'banned') {
        return ['success' => false, 'error' => 'Your account has been banned.'];
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Set session variables (needed for both pending and approved users)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['account_status'];

    if ($user['account_status'] === 'pending') {
        return ['success' => true, 'status' => 'pending'];
    }

    return ['success' => true, 'status' => 'approved'];
}

/**
 * Register a new admin
 */
function register_admin(array $data): array {
    $pdo = get_db();

    // Validate inputs
    if (empty($data['username']) || strlen($data['username']) < 3 || strlen($data['username']) > 100) {
        return ['success' => false, 'error' => 'Username must be between 3 and 100 characters.'];
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    if (empty($data['password']) || strlen($data['password']) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered.'];
    }

    // Check username uniqueness
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$data['username']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username already taken.'];
    }

    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // Insert admin
    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['username'],
        $data['email'],
        $password_hash
    ]);

    return ['success' => true];
}

/**
 * Login admin
 */
function login_admin(string $email, string $password): array {
    $pdo = get_db();

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Regenerate session ID
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];

    return ['success' => true];
}

/**
 * Logout user
 */
function logout_user(): void {
    logout();
    header('Location: /campus-connect/pages/login.php');
    exit;
}

/**
 * Logout admin
 */
function logout_admin(): void {
    logout();
    header('Location: /campus-connect/admin/login.php');
    exit;
}

/**
 * Get current user full data
 */
function get_logged_in_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
