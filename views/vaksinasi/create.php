<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'petugas']);

$current_page = 'vaksinasi';
$page_title   = 'Tambah Vaksinasi';

require_once __DIR__ . '/../../models/Anak.php';
require_once __DIR__ . '/../../models/Vaksin.php';
require_once __DIR__ . '/../../models/Vaksinasi.php';

$anakModel = new Anak();
$vaksinModel = new Vaksin();
$vaksinasiModel = new Vaksinasi();

$selected_anak_id = $_GET['anak_id'] ?? '';
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
        'petugas_id' => $_SESSION['user_id'],
        'catatan' => trim($_POST['catatan'] ?? '')
    ];
    
    if ($vaksinasiModel->create($data)) {
        setFlash('success', 'Data vaksinasi berhasil ditambahkan!');
        redirect(APP_URL . '/views/vaksinasi/index.php');
    } else {
        setFlash('danger', 'Gagal menambahkan data vaksinasi.');
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0"><i class="fas fa-syringe me-2 text-primary"></i>Tambah Data Vaksinasi</h4>
                        <p class="text-muted mb-0">Input data vaksinasi yang telah diberikan</p>
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
                                    <select class="form-select select2" id="anak_id" name="anak_id" required>
                                        <option value="">-- Pilih Anak --</option>
                                        <?php foreach($anak_list as $anak): ?>
                                        <option value="<?= $anak['id'] ?>" 
                                            <?= ($selected_anak_id == $anak['id']) ? 'selected' : '' ?>
                                            data-tanggal-lahir="<?= $anak['tanggal_lahir'] ?>">
                                            <?= e($anak['nama_anak'] . ' (NIK: ' . $anak['nik'] . ')') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="usia_saat_vaksinasi_bulan" class="form-label">Usia Saat Vaksinasi (bulan)</label>
                                    <input type="number" class="form-control" id="usia_saat_vaksinasi_bulan" name="usia_saat_vaksinasi_bulan" min="0" max="60" placeholder="Otomatis terisi" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-4"><i class="fas fa-syringe me-2 text-primary"></i>Data Vaksin</h5>
                                <div class="mb-3">
                                    <label for="jenis_vaksin_id" class="form-label">Jenis Vaksin <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_vaksin_id" name="jenis_vaksin_id" required>
                                        <option value="">-- Pilih Vaksin --</option>
                                        <?php foreach($jenis_vaksin_list as $vaksin): ?>
                                        <option value="<?= $vaksin['id'] ?>"><?= e($vaksin['nama_vaksin'] . ' (' . $vaksin['usia_pemberian_bulan'] . ' bulan)') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_vaksinasi" class="form-label">Tanggal Vaksinasi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_vaksinasi" name="tanggal_vaksinasi" required max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-4"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Teknis</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="batch_number" class="form-label">Batch Number</label>
                                <input type="text" class="form-control" id="batch_number" name="batch_number" placeholder="Nomor batch vaksin">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lokasi_vaksinasi" class="form-label">Lokasi Vaksinasi</label>
                                <input type="text" class="form-control" id="lokasi_vaksinasi" name="lokasi_vaksinasi" value="Puskesmas">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="penyelenggara" class="form-label">Penyelenggara</label>
                                <input type="text" class="form-control" id="penyelenggara" name="penyelenggara" value="Dinas Kesehatan">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="catatan" class="form-label">Catatan</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Efek samping, kondisi anak, dll"></textarea>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <div class="alert alert-info">
                            <i class="fas fa-user-md me-2"></i>
                            <strong>Petugas:</strong> <?= e($_SESSION['nama_lengkap']) ?> (<?= e($_SESSION['role']) ?>)
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-redo me-2"></i>Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Data</button>
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
    
    // Menggunakan jQuery karena kita sudah import Select2
    $('#anak_id').on('change', calculateAge);
    tanggalInput.addEventListener('change', calculateAge);
    
    // Hitung saat pertama kali load jika anak_id dari URL
    if(anakSelect.value) calculateAge();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>