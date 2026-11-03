<?php
/**
 * AuthController — handle login & logout.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userModel = new User();

switch ($action) {
    case 'login':
        handleLogin($userModel);
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        redirect(APP_URL . '/views/auth/login.php');
}

// ============================================================
// LOGIN
// ============================================================

function handleLogin(User $userModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(APP_URL . '/views/auth/login.php');
        return;
    }

    // CSRF protection
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        redirect(APP_URL . '/views/auth/login.php?error=empty');
        return;
    }

    $user = $userModel->login($username, $password);

    if (!$user) {
        // Log minimal — jangan bocorkan password / hash
        error_log('Login failed for username: ' . $username);
        redirect(APP_URL . '/views/auth/login.php?error=invalid');
        return;
    }

    // Regenerate session id → mitigasi session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']      = (int) $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['is_active']    = (bool) $user['is_active'];
    $_SESSION['logged_in']    = true;

    error_log('Login success: ' . $user['username'] . ' (' . $user['role'] . ')');

    // Redirect balik ke halaman yang tadi diminta (kalau ada)
    $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/views/dashboard/index.php');
    unset($_SESSION['redirect_after_login']);

    redirect($redirect);
}

// ============================================================
// LOGOUT
// ============================================================

function handleLogout(): void
{
    // Bersihkan semua data session
    $_SESSION = [];

    // Hapus cookie session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    redirect(APP_URL . '/views/auth/login.php');
}