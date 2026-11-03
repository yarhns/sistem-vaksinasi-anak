<?php
// Paksa tampilkan semua error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
/**
 * Konfigurasi aplikasi global.
 * Auto-load config/local.php kalau ada (env lokal).
 */

// Load config lokal kalau tersedia (development)
if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}

// Start session dengan security flags
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // aktifkan kalau pakai HTTPS
    ]);
    session_start();
}

// Deteksi environment
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Error handling sesuai environment
error_reporting(E_ALL);
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $logDir = __DIR__ . '/../logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        ini_set('error_log', $logDir . '/php-error.log');
    }
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Constants
if (!defined('APP_NAME')) define('APP_NAME', 'Sistem Vaksinasi Anak');
if (!defined('APP_URL'))  define('APP_URL', getenv('APP_URL') ?: 'http://localhost/sistem-vaksinasi');
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));

// Include database & helpers
require_once __DIR__ . '/database.php';
require_once APP_ROOT . '/includes/functions.php';