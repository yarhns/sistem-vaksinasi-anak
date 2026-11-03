<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'petugas']);

$current_page = 'vaksinasi';
$page_title   = 'Edit Vaksinasi';

require_once __DIR__ . '/../../models/Anak.php';
require_once __DIR__ . '/../../models/Vaksin.php';
require_once __DIR__ . '/../../models/Vaksinasi.php';

$anakModel = new Anak();
$vaksinModel = new Vaksin();
$vaksinasiModel = new Vaksinasi();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$vaksinasi = $vaksinasiModel->getById($id);

if (!$vaksinasi) {
    setFlash('danger', 'Data vaksinasi tidak ditemukan!');
    redirect(APP_URL . '/views/vaksinasi/index.php');
}

$anak_list = $anakModel->getAll();
$jenis_vaksin_list = $vaksinModel->getAllJenisVaksin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $data = [
        'anak_id' => $_POST['anak_id'],
        'jenis_vaksin_id' => $_POST['jenis_vaksin_id'],
        'tanggal_vaksinasi' => $_POST['tanggal_vaksinasi'],
        'usia_saat_vaksinasi_bulan' => $_POST['usia_saat_vaksinasi_bulan'] ?? null,
        'batch_number' => trim($_POST['batch_number'] ?? ''),
        'lokasi_vaksinasi' => trim($_POST['lokasi_vaksinasi'] ?? ''),
        'penyelenggara' => trim($_POST['penyelenggara'] ?? ''),
        'catatan' => trim($_POST['catatan'] ?? '')
    ];
    
    if ($vaksinasiModel->update($id, $data)) {
        setFlash('success', 'Data vaksinasi berhasil diperbarui!');
        redirect(APP_URL . '/views/vaksinasi/index.php');
    } else {
        setFlash('danger', 'Gagal memperbarui data vaksinasi.');
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
                        <h4 class="card-title mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Data Vaksinasi</h4>
                        <p class="text-muted mb-0">Anak: <strong><?= e($vaksinasi['nama_anak']) ?></strong></p>
                    </div>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-4"><i class="fas fa-child me-2 text-primary"></i>Data Anak</h5>
                                <div class="mb-3">
                                    <label for="anak_id" class="form-label">Pilih Anak <span class="text-danger">*</span></label>
                                    <select class="form-select" id="anak_id" name="anak_id" required>
                                        <option value="">-- Pilih Anak --</option>
                                        <?php foreach($anak_list as $anak): ?>
                                        <option value="<?= $anak['id'] ?>" 
                                            <?= ($vaksinasi['anak_id'] == $anak['id']) ? 'selected' : '' ?>
                                            data-tanggal-lahir="<?= $anak['tanggal_lahir'] ?>">
                                            <?= e($anak['nama_anak'] . ' (NIK: ' . $anak['nik'] . ')') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="usia_saat_vaksinasi_bulan" class="form-label">Usia Saat Vaksinasi (bulan)</label>
                                    <input type="number" class="form-control" id="usia_saat_vaksinasi_bulan" name="usia_saat_vaksinasi_bulan" min="0" max="60" value="<?= e($vaksinasi['usia_saat_vaksinasi_bulan']) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-4"><i class="fas fa-syringe me-2 text-primary"></i>Data Vaksin</h5>
                                <div class="mb-3">
                                    <label for="jenis_vaksin_id" class="form-label">Jenis Vaksin <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_vaksin_id" name="jenis_vaksin_id" required>
                                        <option value="">-- Pilih Vaksin --</option>
                                        <?php foreach($jenis_vaksin_list as $vaksin): ?>
                                        <option value="<?= $vaksin['id'] ?>" <?= ($vaksinasi['jenis_vaksin_id'] == $vaksin['id']) ? 'selected' : '' ?>>
                                            <?= e($vaksin['nama_vaksin'] . ' (' . $vaksin['kode_vaksin'] . ')') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_vaksinasi" class="form-label">Tanggal Vaksinasi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_vaksinasi" name="tanggal_vaksinasi" required max="<?= date('Y-m-d') ?>" value="<?= e($vaksinasi['tanggal_vaksinasi']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-4"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Teknis</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="batch_number" class="form-label">Batch Number</label>
                                <input type="text" class="form-control" id="batch_number" name="batch_number" value="<?= e($vaksinasi['batch_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lokasi_vaksinasi" class="form-label">Lokasi Vaksinasi</label>
                                <input type="text" class="form-control" id="lokasi_vaksinasi" name="lokasi_vaksinasi" required value="<?= e($vaksinasi['lokasi_vaksinasi']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="penyelenggara" class="form-label">Penyelenggara</label>
                                <input type="text" class="form-control" id="penyelenggara" name="penyelenggara" required value="<?= e($vaksinasi['penyelenggara']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="catatan" class="form-label">Catatan</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3"><?= e($vaksinasi['catatan'] ?? '') ?></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anakSelect = document.getElementById('anak_id');
    const tanggalInput = document.getElementById('tanggal_vaksinasi');
    const usiaInput = document.getElementById('usia_saat_vaksinasi_bulan');
    
    function calculateAge() {
        const selectedOption = anakSelect.options[anakSelect.selectedIndex];
        const tanggalLahir = selectedOption.getAttribute('data-tanggal-lahir');
        const tanggalVaksinasi = tanggalInput.value;
        
        if (tanggalLahir && tanggalVaksinasi) {
            const birthDate = new Date(tanggalLahir);
            const vaxDate = new Date(tanggalVaksinasi);
            
            let months = (vaxDate.getFullYear() - birthDate.getFullYear()) * 12;
            months -= birthDate.getMonth();
            months += vaxDate.getMonth();
            
            if (vaxDate.getDate() < birthDate.getDate()) months--;
            
            if (months >= 0) usiaInput.value = months;
        }
    }
    
    anakSelect.addEventListener('change', calculateAge);
    tanggalInput.addEventListener('change', calculateAge);
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>