<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Set current page for sidebar
$current_page = 'vaksinasi';

// Include models
require_once __DIR__ . '/../../models/Anak.php';
require_once __DIR__ . '/../../models/Vaksinasi.php';
require_once __DIR__ . '/../../models/Vaksin.php';

$anakModel = new Anak();
$vaksinasiModel = new Vaksinasi();
$vaksinModel = new Vaksin();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireRole(['admin', 'petugas']);
    verifyCsrf();
    if ($vaksinasiModel->delete((int)$_POST['delete_id'])) {
        setFlash('success', 'Data vaksinasi berhasil dihapus!');
    } else {
        setFlash('danger', 'Gagal menghapus data vaksinasi!');
    }
    redirect(APP_URL . '/views/vaksinasi/index.php');
}

// Get filter parameters
// Get filter parameters
$filter_anak = $_GET['filter_anak'] ?? '';
$filter_vaksin = $_GET['filter_vaksin'] ?? '';
$filter_bulan = $_GET['filter_bulan'] ?? '';  // Changed from date('m')
$filter_tahun = $_GET['filter_tahun'] ?? '';  // Changed from date('Y')

// Build query for vaksinasi data
$query = "SELECT 
            v.*,
            a.nik,
            a.nama_anak,
            j.kode_vaksin,
            j.nama_vaksin,
            u.nama_lengkap as nama_petugas
          FROM vaksinasi v
          JOIN anak a ON v.anak_id = a.id
          JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
          LEFT JOIN users u ON v.petugas_id = u.id
          WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if(!empty($filter_anak)) {
    $query .= " AND (a.nik LIKE ? OR a.nama_anak LIKE ?)";
    $search_term = "%$filter_anak%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if(!empty($filter_vaksin)) {
    $query .= " AND j.id = ?";
    $params[] = $filter_vaksin;
    $types .= 'i';
}

// Perbaikan: Filter bulan dan tahun terpisah
if(!empty($filter_bulan)) {
    $query .= " AND MONTH(v.tanggal_vaksinasi) = ?";
    $params[] = $filter_bulan;
    $types .= 'i';
}

if(!empty($filter_tahun)) {
    $query .= " AND YEAR(v.tanggal_vaksinasi) = ?";
    $params[] = $filter_tahun;
    $types .= 'i';
}

$query .= " ORDER BY v.tanggal_vaksinasi DESC, v.created_at DESC";



// Execute query
$stmt = $GLOBALS['db']->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vaksinasi_list = $result->fetch_all(MYSQLI_ASSOC);

// Get list of anak for filter dropdown
$anak_list = $anakModel->getAll();

// Get list of vaksin for filter dropdown
$jenis_vaksin_list = $vaksinModel->getAllJenisVaksin();

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
                                <i class="fas fa-syringe me-2 text-primary"></i>
                                Data Vaksinasi
                            </h4>
                            <p class="text-muted mb-0">
                                Kelola data vaksinasi yang telah diberikan kepada anak
                            </p>
                        </div>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Vaksinasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Data
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_anak" class="form-label">Cari Anak</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="filter_anak" 
                                   name="filter_anak" 
                                   placeholder="NIK atau Nama Anak"
                                   value="<?php echo htmlspecialchars($filter_anak); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="filter_vaksin" class="form-label">Jenis Vaksin</label>
                            <select class="form-select" id="filter_vaksin" name="filter_vaksin">
                                <option value="">Semua Vaksin</option>
                                <?php foreach($jenis_vaksin_list as $vaksin): ?>
                                <option value="<?php echo $vaksin['id']; ?>" 
                                    <?php echo ($filter_vaksin == $vaksin['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vaksin['nama_vaksin']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="filter_bulan" class="form-label">Bulan</label>
                            <select class="form-select" id="filter_bulan" name="filter_bulan">
                                <?php
                                $months = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                foreach($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" 
                                    <?php echo ($filter_bulan == $num) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="filter_tahun" class="form-label">Tahun</label>
                            <select class="form-select" id="filter_tahun" name="filter_tahun">
                                <?php
                                $current_year = date('Y');
                                for($year = $current_year; $year >= $current_year - 5; $year--): ?>
                                <option value="<?php echo $year; ?>" 
                                    <?php echo ($filter_tahun == $year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Vaksinasi</h6>
                        <div class="number"><?php echo count($vaksinasi_list); ?></div>
                    </div>
                    <i class="fas fa-syringe"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Anak Divaksin</h6>
                        <?php
                        $query_unique = "SELECT COUNT(DISTINCT anak_id) as total FROM vaksinasi";
                        if(!empty($filter_bulan) && !empty($filter_tahun)) {
                            $query_unique .= " WHERE MONTH(tanggal_vaksinasi) = ? AND YEAR(tanggal_vaksinasi) = ?";
                            $stmt = $GLOBALS['db']->prepare($query_unique);
                            $stmt->bind_param("ii", $filter_bulan, $filter_tahun);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } else {
                            $result = $GLOBALS['db']->query($query_unique);
                        }
                        $unique_anak = $result->fetch_assoc()['total'];
                        ?>
                        <div class="number"><?php echo $unique_anak; ?></div>
                    </div>
                    <i class="fas fa-child"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Jenis Vaksin</h6>
                        <?php
                        $query_vaksin = "SELECT COUNT(DISTINCT jenis_vaksin_id) as total FROM vaksinasi";
                        if(!empty($filter_bulan) && !empty($filter_tahun)) {
                            $query_vaksin .= " WHERE MONTH(tanggal_vaksinasi) = ? AND YEAR(tanggal_vaksinasi) = ?";
                            $stmt = $GLOBALS['db']->prepare($query_vaksin);
                            $stmt->bind_param("ii", $filter_bulan, $filter_tahun);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } else {
                            $result = $GLOBALS['db']->query($query_vaksin);
                        }
                        $unique_vaksin = $result->fetch_assoc()['total'];
                        ?>
                        <div class="number"><?php echo $unique_vaksin; ?></div>
                    </div>
                    <i class="fas fa-vial"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card bg-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Petugas Aktif</h6>
                        <?php
                        $query_petugas = "SELECT COUNT(DISTINCT petugas_id) as total FROM vaksinasi WHERE petugas_id IS NOT NULL";
                        if(!empty($filter_bulan) && !empty($filter_tahun)) {
                            $query_petugas .= " AND MONTH(tanggal_vaksinasi) = ? AND YEAR(tanggal_vaksinasi) = ?";
                            $stmt = $GLOBALS['db']->prepare($query_petugas);
                            $stmt->bind_param("ii", $filter_bulan, $filter_tahun);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } else {
                            $result = $GLOBALS['db']->query($query_petugas);
                        }
                        $unique_petugas = $result->fetch_assoc()['total'];
                        ?>
                        <div class="number"><?php echo $unique_petugas; ?></div>
                    </div>
                    <i class="fas fa-user-md"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Daftar Vaksinasi
                    </h5>
                    <div>
                        <span class="badge bg-primary">
                            Total: <?php echo count($vaksinasi_list); ?> data
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Nama Anak</th>
                                    <th>NIK</th>
                                    <th>Vaksin</th>
                                    <th>Usia</th>
                                    <th>Batch No</th>
                                    <th>Lokasi</th>
                                    <th>Petugas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($vaksinasi_list)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-syringe fa-2x mb-3 text-muted"></i>
                                        <p class="text-muted">Tidak ada data vaksinasi</p>
                                        <a href="create.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Tambah Data Vaksinasi
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($vaksinasi_list as $index => $vaksin): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($vaksin['tanggal_vaksinasi'])); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($vaksin['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($vaksin['nama_anak']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($vaksin['nik']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($vaksin['kode_vaksin']); ?>
                                        </span><br>
                                        <small><?php echo htmlspecialchars($vaksin['nama_vaksin']); ?></small>
                                    </td>
                                    <td>
                                        <?php if($vaksin['usia_saat_vaksinasi_bulan']): ?>
                                        <span class="badge bg-secondary">
                                            <?php echo $vaksin['usia_saat_vaksinasi_bulan']; ?> bulan
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($vaksin['batch_number']): ?>
                                        <code><?php echo htmlspecialchars($vaksin['batch_number']); ?></code>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($vaksin['lokasi_vaksinasi']); ?></small>
                                    </td>
                                    <td>
                                        <?php if($vaksin['nama_petugas']): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($vaksin['nama_petugas']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" 
                                                    class="btn btn-info" 
                                                    title="Detail"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?php echo $vaksin['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="edit.php?id=<?php echo $vaksin['id']; ?>" 
                                               class="btn btn-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data vaksinasi ini?')">
    <?= csrfField() ?>
    <input type="hidden" name="delete_id" value="<?= $vaksin['id'] ?>">
    <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
</form>
                                        </div>
                                        
                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="detailModal<?php echo $vaksin['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-syringe me-2"></i>
                                                            Detail Vaksinasi
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Data Anak</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Nama Anak</th>
                                                                        <td><?php echo htmlspecialchars($vaksin['nama_anak']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>NIK</th>
                                                                        <td><?php echo htmlspecialchars($vaksin['nik']); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Data Vaksin</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Jenis Vaksin</th>
                                                                        <td>
                                                                            <?php echo htmlspecialchars($vaksin['nama_vaksin']); ?><br>
                                                                            <small class="text-muted">(<?php echo htmlspecialchars($vaksin['kode_vaksin']); ?>)</small>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Tanggal Vaksinasi</th>
                                                                        <td><?php echo date('d/m/Y', strtotime($vaksin['tanggal_vaksinasi'])); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <h6>Informasi Teknis</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Batch Number</th>
                                                                        <td><?php echo $vaksin['batch_number'] ? htmlspecialchars($vaksin['batch_number']) : '-'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Usia Saat Vaksin</th>
                                                                        <td>
                                                                            <?php echo $vaksin['usia_saat_vaksinasi_bulan'] ? $vaksin['usia_saat_vaksinasi_bulan'] . ' bulan' : '-'; ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Lokasi</th>
                                                                        <td><?php echo htmlspecialchars($vaksin['lokasi_vaksinasi']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Penyelenggara</th>
                                                                        <td><?php echo htmlspecialchars($vaksin['penyelenggara']); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Petugas & Catatan</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Petugas</th>
                                                                        <td><?php echo $vaksin['nama_petugas'] ? htmlspecialchars($vaksin['nama_petugas']) : '-'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Catatan</th>
                                                                        <td><?php echo $vaksin['catatan'] ? nl2br(htmlspecialchars($vaksin['catatan'])) : '-'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Dibuat</th>
                                                                        <td><?php echo date('d/m/Y H:i', strtotime($vaksin['created_at'])); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <a href="edit.php?id=<?php echo $vaksin['id']; ?>" class="btn btn-primary">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
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