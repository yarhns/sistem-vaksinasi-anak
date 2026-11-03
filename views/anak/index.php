<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin(); // Semua role bisa lihat, tapi hanya admin/petugas yang bisa ubah

$current_page = 'anak';
$page_title   = 'Data Anak';

require_once __DIR__ . '/../../models/Anak.php';
$anakModel = new Anak();

// HANDLE DELETE (Ganti GET menjadi POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireRole(['admin', 'petugas']); // Hanya admin/petugas yang boleh hapus
    verifyCsrf();
    
    $id = (int) $_POST['delete_id'];
    if ($anakModel->delete($id)) {
        setFlash('success', 'Data anak berhasil dihapus!');
    } else {
        setFlash('danger', 'Gagal menghapus data anak!');
    }
    redirect(APP_URL . '/views/anak/index.php');
}

// HANDLE SEARCH
$keyword = trim($_GET['search'] ?? '');
if ($keyword !== '') {
    $searchResults = $anakModel->search($keyword);
} else {
    $searchResults = $anakModel->getAll();
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">
                                <i class="fas fa-child me-2 text-primary"></i>
                                Data Anak
                            </h4>
                            <p class="text-muted mb-0">
                                Kelola data anak yang terdaftar dalam sistem vaksinasi
                            </p>
                        </div>
                        <?php if (hasRole('admin') || hasRole('petugas')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Data Anak
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Cari berdasarkan NIK, nama anak, atau nama orang tua..."
                                       value="<?= e($keyword) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="index.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>NIK</th>
                                    <th>Nama Anak</th>
                                    <th>TTL</th>
                                    <th>JK</th>
                                    <th>Orang Tua</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($searchResults)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
    <img src="<?= e(APP_URL) ?>/assets/img/empty.svg" alt="Data Kosong" style="width: 150px; opacity: 0.7; margin-bottom: 15px;">
    <h6 class="text-muted">Data tidak ditemukan</h6>
    <p class="text-muted small">Coba ubah kata kunci pencarian atau tambah data baru.</p>
</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($searchResults as $index => $anak): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><span class="badge bg-primary"><?= e($anak['kode_anak']) ?></span></td>
                                    <td><?= e($anak['nik']) ?></td>
                                    <td><strong><?= e($anak['nama_anak']) ?></strong></td>
                                    <td>
                                        <small>
                                            <?= e($anak['tempat_lahir']) ?>,<br>
                                            <?= formatTanggal($anak['tanggal_lahir']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($anak['jenis_kelamin'] === 'L'): ?>
                                            <span class="badge bg-info">Laki-laki</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: #e83e8c;">Perempuan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            Ayah: <?= e($anak['nama_ayah']) ?><br>
                                            Ibu: <?= e($anak['nama_ibu']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="<?= e(APP_URL) ?>/views/vaksinasi/create.php?anak_id=<?= $anak['id'] ?>" 
                                               class="btn btn-sm btn-success" title="Tambah Vaksinasi">
                                                <i class="fas fa-syringe"></i>
                                            </a>
                                            
                                            <?php if (hasRole('admin') || hasRole('petugas')): ?>
                                            <a href="edit.php?id=<?= $anak['id'] ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Form Hapus dengan POST & CSRF -->
                                            <form method="POST" action="" class="d-inline" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="delete_id" value="<?= $anak['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>