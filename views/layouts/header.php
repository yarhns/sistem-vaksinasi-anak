<?php
/**
 * Layout header.
 * Wajib: $current_page dan $page_title sudah di-set sebelum include.
 * Wajib: user sudah login (dijaga oleh requireLogin() di halaman).
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Guard: kalau belum login, tendang ke login (jaga-jaga)
if (!isLoggedIn()) {
    redirect(APP_URL . '/views/auth/login.php');
}

$current_page = $current_page ?? 'dashboard';
$page_title   = $page_title   ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - <?= e($page_title) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css?v=2.0">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= e(APP_URL) ?>/assets/img/favicon.ico">
</head>
<body>
    <div class="container-fluid">
        <div class="row">

            <?php include __DIR__ . '/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content" id="mainContent">

                <?php
                // Flash message global (dari setFlash di controller)
                echo renderFlash();
                ?>