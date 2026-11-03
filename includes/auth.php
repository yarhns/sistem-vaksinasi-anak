<?php
/**
 * Authentication & Authorization helpers.
 * File ini menggantikan includes/auth_check.php.
 *
 * Cara pakai di halaman:
 *   require_once __DIR__ . '/../../config/config.php';
 *   require_once __DIR__ . '/../../includes/auth.php';
 *   requireLogin();               // semua user login
 *   requireRole(['admin']);       // hanya admin
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Wajib login. Redirect ke halaman login jika belum.
 */
function requireLogin(): void
{
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        header('Location: ' . APP_URL . '/views/auth/login.php');
        exit();
    }

    if (isset($_SESSION['is_active']) && $_SESSION['is_active'] === false) {
        session_destroy();
        header('Location: ' . APP_URL . '/views/auth/login.php?error=inactive');
        exit();
    }
}

/**
 * Wajib login + role tertentu.
 * @param array $allowedRoles contoh: ['admin'], ['admin','petugas']
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    $role = $_SESSION['role'] ?? null;
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        header('Location: ' . APP_URL . '/views/dashboard/index.php?error=forbidden');
        exit();
    }
}

function hasRole(string $role): bool
{
    return ($_SESSION['role'] ?? null) === $role;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}