<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$current_page = 'users';
$page_title   = 'Edit Pengguna';

require_once __DIR__ . '/../../models/User.php';
$userModel = new User();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = $userModel->getUserById($id);

if (!$user) {
    setFlash('danger', 'Pengguna tidak ditemukan!');
    redirect(APP_URL . '/views/users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $new_password = $_POST['password'] ?? '';

    if (!empty($email) && $userModel->emailExists($email, $id)) {
        setFlash('danger', 'Email sudah digunakan oleh pengguna lain!');
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        setFlash('danger', 'Password baru minimal 6 karakter!');
    } else {
        // Update profil
        $data = [
            'nama_lengkap' => $nama,
            'role' => $role,
            'email' => $email,
            'no_hp' => $no_hp,
            'is_active' => $user['is_active'] // Pertahankan status
        ];
        $userModel->updateUser($id, $data);

        // Update password jika diisi
        if (!empty($new_password)) {
            $userModel->updatePassword($id, $new_password);
        }

        setFlash('success', 'Data pengguna berhasil diperbarui!');
        redirect(APP_URL . '/views/users/index.php');
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
                        <h4 class="card-title mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Pengguna</h4>
                        <p class="text-muted mb-0">Edit data: <?= e($user['nama_lengkap']) ?></p>
                    </div>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
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
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                                    <option value="petugas" <?= ($user['role'] === 'petugas') ? 'selected' : '' ?>>Petugas</option>
                                    <option value="orangtua" <?= ($user['role'] === 'orangtua') ? 'selected' : '' ?>>Orang Tua</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6">
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
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Perbarui Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>