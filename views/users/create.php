<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$current_page = 'users';
$page_title   = 'Tambah Pengguna';

require_once __DIR__ . '/../../models/User.php';
$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (strlen($password) < 6) {
        setFlash('danger', 'Password minimal 6 karakter!');
    } elseif ($userModel->usernameExists($username)) {
        setFlash('danger', 'Username sudah digunakan!');
    } elseif (!empty($email) && $userModel->emailExists($email)) {
        setFlash('danger', 'Email sudah digunakan!');
    } else {
        $data = [
            'username' => $username,
            'password' => $password,
            'nama_lengkap' => $nama,
            'role' => $role,
            'email' => $email,
            'no_hp' => $no_hp
        ];
        
        if ($userModel->createUser($data)) {
            setFlash('success', 'Pengguna berhasil ditambahkan!');
            redirect(APP_URL . '/views/users/index.php');
        } else {
            setFlash('danger', 'Gagal menambahkan pengguna.');
        }
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
                        <h4 class="card-title mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Tambah Pengguna Baru</h4>
                        <p class="text-muted mb-0">Tambahkan pengguna baru ke sistem</p>
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
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required minlength="3" placeholder="Minimal 3 karakter">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required placeholder="Nama lengkap pengguna">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="petugas">Petugas Vaksinasi</option>
                                    <option value="orangtua">Orang Tua</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="email@contoh.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="no_hp" class="form-label">No. HP</label>
                                <input type="text" class="form-control phone-input" id="no_hp" name="no_hp" placeholder="0812-3456-7890">
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-redo me-2"></i>Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Pengguna</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>