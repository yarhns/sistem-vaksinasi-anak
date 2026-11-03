<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']); // Hanya admin

$current_page = 'users';
$page_title   = 'Manajemen Pengguna';

require_once __DIR__ . '/../../models/User.php';
$userModel = new User();

// Handle actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        if ($userModel->deleteUser($id)) {
            setFlash('success', 'Pengguna berhasil dihapus!');
        } else {
            setFlash('danger', 'Gagal menghapus pengguna (atau Anda mencoba menghapus akun sendiri).');
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $targetUser = $userModel->getUserById($id);
        if ($targetUser) {
            $newStatus = $targetUser['is_active'] ? 0 : 1;
            // Update status via model (kita pakai updateUser yang sudah ada)
            $userModel->updateUser($id, [
                'nama_lengkap' => $targetUser['nama_lengkap'],
                'email' => $targetUser['email'],
                'no_hp' => $targetUser['no_hp'],
                'role' => $targetUser['role'],
                'is_active' => $newStatus
            ]);
            setFlash('success', 'Status pengguna berhasil diubah!');
        }
    }
    redirect(APP_URL . '/views/users/index.php');
}

$users = $userModel->getAllUsers();
include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid p-0">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0"><i class="fas fa-users me-2 text-primary"></i>Manajemen Pengguna</h4>
                        <p class="text-muted mb-0">Kelola pengguna sistem vaksinasi</p>
                    </div>
                    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Pengguna</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Daftar Pengguna</h5>
                    <span class="badge bg-primary">Total: <?= count($users) ?> pengguna</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>#</th><th>Username</th><th>Nama Lengkap</th><th>Role</th>
                                    <th>Kontak</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= e($user['username']) ?></strong>
                                        <?php if($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info">Anda</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($user['nama_lengkap']) ?></td>
                                    <td>
                                        <?php 
                                        $role_badges = ['admin' => 'danger', 'petugas' => 'warning', 'orangtua' => 'info'];
                                        $badge = $role_badges[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst($user['role']) ?></span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= e($user['email'] ?: '-') ?><br>
                                            <?= e($user['no_hp'] ?: '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= $user['last_login'] ? formatTanggal($user['last_login'], 'd/m/Y H:i') : 'Belum pernah' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Toggle Status POST -->
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Ubah status pengguna ini?')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-<?= $user['is_active'] ? 'secondary' : 'success' ?>" title="Ubah Status">
                                                    <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            </form>

                                            <!-- Hapus POST -->
                                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>