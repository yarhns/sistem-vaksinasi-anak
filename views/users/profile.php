<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(); // Semua role bisa akses profil

$current_page = 'profile';
$page_title   = 'Profil Saya';

require_once __DIR__ . '/../../models/User.php';
$userModel = new User();

$user = $userModel->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    $errors = [];

    // Cek email duplikat
    if (!empty($email) && $userModel->emailExists($email, (int)$_SESSION['user_id'])) {
        $errors[] = 'Email sudah digunakan oleh pengguna lain.';
    }

    // Logika ubah password
    $update_password = false;
    if (!empty($current_password) || !empty($new_password)) {
        if (empty($current_password) || empty($new_password)) {
            $errors[] = 'Untuk mengubah password, isi Password Saat Ini dan Password Baru.';
        } elseif (!$userModel->verifyPassword((int)$_SESSION['user_id'], $current_password)) {
            $errors[] = 'Password saat ini salah.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } else {
            $update_password = true;
        }
    }

    if (empty($errors)) {
        // Update profil dasar
        $userModel->updateUser((int)$_SESSION['user_id'], [
            'nama_lengkap' => $nama,
            'email' => $email,
            'no_hp' => $no_hp,
            'role' => $user['role'], // role tidak berubah
            'is_active' => $user['is_active'] // status tidak berubah
        ]);

        // Update password jika valid
        if ($update_password) {
            $userModel->updatePassword((int)$_SESSION['user_id'], $new_password);
        }

        $_SESSION['nama_lengkap'] = $nama; // Update nama di sidebar
        setFlash('success', 'Profil berhasil diperbarui!');
        redirect(APP_URL . '/views/users/profile.php');
    } else {
        setFlash('danger', implode('<br>', $errors));
        redirect(APP_URL . '/views/users/profile.php');
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid p-0">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0"><i class="fas fa-user-cog me-2 text-primary"></i>Profil Saya</h4>
                        <p class="text-muted mb-0">Kelola data profil Anda</p>
                    </div>
                    <a href="<?= e(APP_URL) ?>/views/dashboard/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-user-circle fa-5x text-primary mb-2"></i>
                            <h5><?= e($user['nama_lengkap']) ?></h5>
                            <?php 
                            $role_badges = ['admin' => 'danger', 'petugas' => 'warning', 'orangtua' => 'info'];
                            $badge = $role_badges[$user['role']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($user['role']) ?></span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <div class="form-control bg-light"><strong><?= e($user['username']) ?></strong></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required value="<?= e($user['nama_lengkap']) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($user['email']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="no_hp" class="form-label">No. HP</label>
                                <input type="text" class="form-control phone-input" id="no_hp" name="no_hp" value="<?= e($user['no_hp']) ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3"><i class="fas fa-key me-2 text-warning"></i>Ubah Password</h5>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Kosongkan kedua kolom di bawah jika tidak ingin mengubah password.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Masukkan password saat ini">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Minimal 6 karakter" minlength="6">
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-history me-2"></i>
                            <strong>Login terakhir:</strong> 
                            <?= $user['last_login'] ? formatTanggal($user['last_login'], 'd/m/Y H:i') : 'Belum pernah login' ?>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?= e(APP_URL) ?>/views/dashboard/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>