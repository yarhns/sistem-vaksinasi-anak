<?php
/**
 * Sidebar navigasi.
 * Dipanggil dari header.php. $current_page wajib di-set.
 */

$current_page = $current_page ?? '';
$role         = $_SESSION['role'] ?? '';
$namaUser     = $_SESSION['nama_lengkap'] ?? 'Pengguna';

/**
 * Helper kecil untuk render link aktif.
 */
$navLink = function (string $page, string $url, string $icon, string $label) use ($current_page) {
    $active = ($current_page === $page) ? ' active' : '';
    return '<li class="nav-item">'
         . '<a class="nav-link' . $active . '" href="' . e($url) . '">'
         . '<i class="fas ' . e($icon) . ' me-2"></i> ' . e($label)
         . '</a></li>';
};
?>
<div class="col-md-2 col-lg-2 p-0 sidebar d-flex flex-column" id="sidebar">
    <div class="p-3 flex-grow-1">
        <!-- Logo Aplikasi -->
        <div class="d-flex align-items-center justify-content-center mb-4 mt-2">
            <div style="background: var(--primary-color); width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.4);">
                <i class="fas fa-syringe text-white" style="font-size: 1.3rem;"></i>
            </div>
            <h4 class="mb-0 ms-3 text-white" style="font-weight: 700; letter-spacing: -0.5px;">
                Sistem<span style="font-weight: 300;">Vaksin</span>
            </h4>
        </div>

        <!-- Profil Singkat -->
<div class="text-center mb-4 mt-2">
    <?php 
    // Ambil huruf pertama dari nama untuk avatar
    $initial = strtoupper(substr($namaUser, 0, 1)); 
    ?>
    <div class="mx-auto mb-2" style="width: 64px; height: 64px; background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.4);">
        <span style="font-size: 1.8rem; font-weight: 700; color: white;"><?= $initial ?></span>
    </div>
    <h6 class="mb-1 text-white font-weight-bold"><?= e($namaUser) ?></h6>
    <span class="badge bg-secondary" style="font-size: 0.7rem; opacity: 0.8; letter-spacing: 1px;">
        <?= e(strtoupper($role)) ?>
    </span>
</div>

        <hr class="text-secondary">

        <!-- Menu Navigasi -->
        <ul class="nav flex-column">
            <li class="nav-item mt-1">
                <small class="text-uppercase px-3" style="color: #64748B; font-size: 0.7rem; font-weight: 700;">Menu Utama</small>
            </li>

            <?= $navLink('dashboard', APP_URL . '/views/dashboard/index.php', 'fa-home', 'Dashboard') ?>
            <?= $navLink('anak',      APP_URL . '/views/anak/index.php',      'fa-child', 'Data Anak') ?>
            <?= $navLink('vaksinasi', APP_URL . '/views/vaksinasi/index.php', 'fa-syringe', 'Vaksinasi') ?>
            <?= $navLink('laporan',   APP_URL . '/views/laporan/index.php',   'fa-chart-pie', 'Laporan') ?>

            <?php if ($role === 'admin'): ?>
                <li class="nav-item mt-3">
                    <small class="text-uppercase px-3" style="color: #64748B; font-size: 0.7rem; font-weight: 700;">Administrator</small>
                </li>
                <?= $navLink('users', APP_URL . '/views/users/index.php', 'fa-users-cog', 'Pengguna') ?>
            <?php endif; ?>

            <li class="nav-item mt-3">
                <small class="text-uppercase px-3" style="color: #64748B; font-size: 0.7rem; font-weight: 700;">Akun</small>
            </li>
            <?= $navLink('profile', APP_URL . '/views/users/profile.php', 'fa-user-circle', 'Profil Saya') ?>
        </ul>
    </div>

    <!-- Footer Sidebar (Copyright & Logout) -->
    <div class="p-3 mt-auto">
        <hr class="text-secondary">
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= e(APP_URL) ?>/index.php?action=logout" style="color: #F87171 !important;">
    <i class="fas fa-sign-out-alt me-2"></i> Keluar
</a>
            </li>
        </ul>
        
        <!-- REQUEST FOOTER KAMU -->
        <div class="text-center" style="color: #64748B; font-size: 0.75rem;">
            <i class="far fa-copyright"></i> 2026 - Dear Haens
        </div>
    </div>
</div>