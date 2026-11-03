<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'petugas']); // Hanya admin & petugas yang bisa edit

$current_page = 'anak';
$page_title   = 'Edit Data Anak';

require_once __DIR__ . '/../../models/Anak.php';
$anakModel = new Anak();

// Get ID from URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get current data
$anak = $anakModel->getById($id);

// Redirect if not found
if (!$anak) {
    setFlash('danger', 'Data anak tidak ditemukan!');
    redirect(APP_URL . '/views/anak/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); // Cek token keamanan
    
    $data = [
        'nik' => trim($_POST['nik'] ?? ''),
        'nama_anak' => trim($_POST['nama_anak'] ?? ''),
        'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
        'nama_ayah' => trim($_POST['nama_ayah'] ?? ''),
        'nama_ibu' => trim($_POST['nama_ibu'] ?? ''),
        'alamat' => trim($_POST['alamat'] ?? ''),
        'kelurahan' => trim($_POST['kelurahan'] ?? ''),
        'kecamatan' => trim($_POST['kecamatan'] ?? ''),
        'kabupaten' => trim($_POST['kabupaten'] ?? ''),
        'provinsi' => trim($_POST['provinsi'] ?? ''),
        'no_hp_ortu' => trim($_POST['no_hp_ortu'] ?? ''),
        'berat_lahir' => !empty($_POST['berat_lahir']) ? (float)$_POST['berat_lahir'] : null,
        'panjang_lahir' => !empty($_POST['panjang_lahir']) ? (float)$_POST['panjang_lahir'] : null,
        'golongan_darah' => $_POST['golongan_darah'] ?? '',
        'alergi' => trim($_POST['alergi'] ?? '')
    ];
    
    // Check if NIK already exists (excluding current record)
    $existing = $anakModel->getByNik($data['nik']);
    if ($existing && $existing['id'] != $id) {
        setFlash('danger', 'NIK sudah terdaftar untuk anak lain!');
    } else {
        $success = $anakModel->update($id, $data);
        if ($success) {
            setFlash('success', 'Data anak berhasil diperbarui!');
            redirect(APP_URL . '/views/anak/index.php');
        } else {
            setFlash('danger', 'Gagal memperbarui data anak!');
        }
    }
    
    // Refresh data after update attempt
    $anak = $anakModel->getById($id);
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
                                <i class="fas fa-edit me-2 text-primary"></i>
                                Edit Data Anak
                            </h4>
                            <p class="text-muted mb-0">
                                Edit data: <strong><?= e($anak['nama_anak']) ?></strong>
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-info me-2">
                                Kode: <?= e($anak['kode_anak']) ?>
                            </span>
                            <a href="<?= e(APP_URL) ?>/views/anak/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" id="formEditAnak">
                        <?= csrfField() ?>
                        
                        <h5 class="mb-4">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Identitas Anak
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nik" name="nik" required maxlength="16"
                                       value="<?= e($anak['nik']) ?>" placeholder="16 digit NIK">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="nama_anak" class="form-label">Nama Anak <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_anak" name="nama_anak" required
                                       value="<?= e($anak['nama_anak']) ?>" placeholder="Nama lengkap anak">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tempat_lahir" class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" required
                                       value="<?= e($anak['tempat_lahir']) ?>" placeholder="Kota/Kabupaten lahir">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required
                                       max="<?= date('Y-m-d') ?>" value="<?= e($anak['tanggal_lahir']) ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L" <?= ($anak['jenis_kelamin'] === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= ($anak['jenis_kelamin'] === 'P') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="berat_lahir" class="form-label">Berat Lahir (kg)</label>
                                <input type="number" class="form-control" id="berat_lahir" name="berat_lahir" step="0.01"
                                       value="<?= e($anak['berat_lahir'] ?? '') ?>" placeholder="Contoh: 3.2">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="panjang_lahir" class="form-label">Panjang Lahir (cm)</label>
                                <input type="number" class="form-control" id="panjang_lahir" name="panjang_lahir" step="0.01"
                                       value="<?= e($anak['panjang_lahir'] ?? '') ?>" placeholder="Contoh: 48.5">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="golongan_darah" class="form-label">Golongan Darah</label>
                                <select class="form-select" id="golongan_darah" name="golongan_darah">
                                    <option value="">Pilih Golongan Darah</option>
                                    <option value="A" <?= ($anak['golongan_darah'] === 'A') ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= ($anak['golongan_darah'] === 'B') ? 'selected' : '' ?>>B</option>
                                    <option value="AB" <?= ($anak['golongan_darah'] === 'AB') ? 'selected' : '' ?>>AB</option>
                                    <option value="O" <?= ($anak['golongan_darah'] === 'O') ? 'selected' : '' ?>>O</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="alergi" class="form-label">Alergi</label>
                                <input type="text" class="form-control" id="alergi" name="alergi"
                                       value="<?= e($anak['alergi'] ?? '') ?>" placeholder="Jika ada alergi">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-4">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Data Orang Tua
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nama_ayah" class="form-label">Nama Ayah</label>
                                <input type="text" class="form-control" id="nama_ayah" name="nama_ayah"
                                       value="<?= e($anak['nama_ayah'] ?? '') ?>" placeholder="Nama lengkap ayah">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="nama_ibu" class="form-label">Nama Ibu</label>
                                <input type="text" class="form-control" id="nama_ibu" name="nama_ibu"
                                       value="<?= e($anak['nama_ibu'] ?? '') ?>" placeholder="Nama lengkap ibu">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="no_hp_ortu" class="form-label">No. HP Orang Tua</label>
                                <input type="text" class="form-control phone-input" id="no_hp_ortu" name="no_hp_ortu"
                                       value="<?= e($anak['no_hp_ortu'] ?? '') ?>" placeholder="0812-3456-7890">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-4">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            Alamat
                        </h5>
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="alamat" class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="2" 
                                          placeholder="Jl. Nama Jalan No. RT/RW"><?= e($anak['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="kelurahan" class="form-label">Kelurahan</label>
                                <input type="text" class="form-control" id="kelurahan" name="kelurahan"
                                       value="<?= e($anak['kelurahan'] ?? '') ?>" placeholder="Nama kelurahan">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="kecamatan" class="form-label">Kecamatan</label>
                                <input type="text" class="form-control" id="kecamatan" name="kecamatan"
                                       value="<?= e($anak['kecamatan'] ?? '') ?>" placeholder="Nama kecamatan">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="kabupaten" class="form-label">Kabupaten/Kota</label>
                                <input type="text" class="form-control" id="kabupaten" name="kabupaten"
                                       value="<?= e($anak['kabupaten'] ?? '') ?>" placeholder="Nama kabupaten/kota">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="provinsi" class="form-label">Provinsi</label>
                                <input type="text" class="form-control" id="provinsi" name="provinsi"
                                       value="<?= e($anak['provinsi'] ?? '') ?>" placeholder="Nama provinsi">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Info Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-history me-2"></i>
                                    <strong>Informasi Data:</strong><br>
                                    <small>
                                        Dibuat: <?= formatTanggal($anak['created_at'], 'd/m/Y H:i') ?><br>
                                        Terakhir update: <?= formatTanggal($anak['updated_at'], 'd/m/Y H:i') ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Catatan:</strong><br>
                                    <small>
                                        Perubahan data anak akan mempengaruhi data vaksinasi terkait.<br>
                                        Pastikan data yang dimasukkan sudah benar.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?= e(APP_URL) ?>/views/anak/index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>