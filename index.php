<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Teruskan ke AuthController jika ada aksi login atau logout
if ($action === 'login' || $action === 'logout') {
    require_once __DIR__ . '/controllers/AuthController.php';
    exit();
}

if (isLoggedIn()) {
    redirect(APP_URL . '/views/dashboard/index.php');
}

redirect(APP_URL . '/views/auth/login.php');