<?php

require_once __DIR__ . '/db.php';

// ── Session helpers ──────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Call this at the top of any page that needs an active admin session.
 * Redirects to the login page if not authenticated.
 */
function requireAdmin(string $loginUrl = '../admin/index.php'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

function requireAdminApi(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['admin_id']);
}

// ── Only handle HTTP actions when this file is the direct request target ─────

if (basename($_SERVER['PHP_SELF']) !== 'auth.php') {
    return; // included as a library – do nothing
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── REGISTER ─────────────────────────────────────────────────────────────
    case 'register':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email already registered.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare('INSERT INTO admins (email, password) VALUES (?, ?)');
        $ins->execute([$email, $hash]);

        echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
        break;

    // ── LOGIN ─────────────────────────────────────────────────────────────────
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, password FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
            exit;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['admin_id']    = $admin['id'];
        $_SESSION['admin_email'] = $email;

        echo json_encode(['success' => true, 'redirect' => 'admin.php']);
        break;

    // ── LOGOUT ───────────────────────────────────────────────────────────────
    case 'logout':
        $_SESSION = [];
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => '../admin/index.php']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
