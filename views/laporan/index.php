<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'petugas']); // Hanya admin/petugas

$current_page = 'laporan';
$page_title   = 'Laporan Vaksinasi';

require_once __DIR__ . '/../../models/Anak.php';
require_once __DIR__ . '/../../models/Vaksinasi.php';
require_once __DIR__ . '/../../models/Vaksin.php';

$anakModel = new Anak();
$vaksinasiModel = new Vaksinasi();
$vaksinModel = new Vaksin();

// ============================================================
// 1. TANGKAP FILTER
// ============================================================
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_bulan = $_GET['bulan'] ?? '';
$filter_kecamatan = $_GET['kecamatan'] ?? '';
$filter_vaksin = $_GET['vaksin'] ?? '';
$filter_jenis_kelamin = $_GET['jenis_kelamin'] ?? '';
$filter_usia_min = (isset($_GET['usia_min']) && $_GET['usia_min'] !== '') ? (int)$_GET['usia_min'] : null;
$filter_usia_max = (isset($_GET['usia_max']) && $_GET['usia_max'] !== '') ? (int)$_GET['usia_max'] : null;
$filter_petugas = $_GET['petugas'] ?? '';
$export = $_GET['export'] ?? '';

// ============================================================
// 2. FILTER LISTS
// ============================================================
$kecamatan_list = [];
$result = $GLOBALS['db']->query("SELECT DISTINCT kecamatan FROM anak WHERE kecamatan IS NOT NULL AND kecamatan != '' ORDER BY kecamatan");
if ($result) $kecamatan_list = $result->fetch_all(MYSQLI_ASSOC);

$jenis_vaksin_list = $vaksinModel->getAllJenisVaksin();

$petugas_list = [];
$petugas_query = "SELECT DISTINCT u.id, u.nama_lengkap FROM vaksinasi v JOIN users u ON v.petugas_id = u.id ORDER BY u.nama_lengkap";
$petugas_result = $GLOBALS['db']->query($petugas_query);
if ($petugas_result) $petugas_list = $petugas_result->fetch_all(MYSQLI_ASSOC);

// ============================================================
// 3. QUERY LAPORAN
// ============================================================
$query_vaksinasi = "SELECT v.*, a.nik, a.nama_anak, a.tanggal_lahir, a.jenis_kelamin, a.kecamatan, a.kelurahan,
    j.kode_vaksin, j.nama_vaksin, u.nama_lengkap as nama_petugas,
    TIMESTAMPDIFF(MONTH, a.tanggal_lahir, v.tanggal_vaksinasi) as usia_bulan
    FROM vaksinasi v
    JOIN anak a ON v.anak_id = a.id
    JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
    LEFT JOIN users u ON v.petugas_id = u.id
    WHERE 1=1";

$query_anak = "SELECT a.* FROM anak a WHERE 1=1";

$conditions_vaksinasi = [];
$params_vaksinasi = [];
$types_vaksinasi = '';

$conditions_anak = [];
$params_anak = [];
$types_anak = '';

if (!empty($filter_tahun)) {
    $conditions_vaksinasi[] = "YEAR(v.tanggal_vaksinasi) = ?";
    $params_vaksinasi[] = $filter_tahun;
    $types_vaksinasi .= 'i';
}

if (!empty($filter_bulan)) {
    $conditions_vaksinasi[] = "MONTH(v.tanggal_vaksinasi) = ?";
    $params_vaksinasi[] = $filter_bulan;
    $types_vaksinasi .= 'i';
}

if (!empty($filter_kecamatan)) {
    $conditions_vaksinasi[] = "a.kecamatan = ?";
    $conditions_anak[] = "kecamatan = ?";
    $params_vaksinasi[] = $filter_kecamatan;
    $params_anak[] = $filter_kecamatan;
    $types_vaksinasi .= 's';
    $types_anak .= 's';
}

if (!empty($filter_vaksin)) {
    $conditions_vaksinasi[] = "j.id = ?";
    $params_vaksinasi[] = $filter_vaksin;
    $types_vaksinasi .= 'i';
}

if (!empty($filter_jenis_kelamin)) {
    $conditions_vaksinasi[] = "a.jenis_kelamin = ?";
    $conditions_anak[] = "jenis_kelamin = ?";
    $params_vaksinasi[] = $filter_jenis_kelamin;
    $params_anak[] = $filter_jenis_kelamin;
    $types_vaksinasi .= 's';
    $types_anak .= 's';
}

if (!empty($filter_petugas)) {
    $conditions_vaksinasi[] = "u.id = ?";
    $params_vaksinasi[] = $filter_petugas;
    $types_vaksinasi .= 'i';
}

if (!empty($conditions_vaksinasi)) $query_vaksinasi .= " AND " . implode(" AND ", $conditions_vaksinasi);
if (!empty($conditions_anak)) $query_anak .= " AND " . implode(" AND ", $conditions_anak);

$query_vaksinasi .= " ORDER BY v.tanggal_vaksinasi DESC, a.nama_anak";
$query_anak .= " ORDER BY nama_anak";

$stmt_vaksinasi = $GLOBALS['db']->prepare($query_vaksinasi);
if (!empty($params_vaksinasi)) $stmt_vaksinasi->bind_param($types_vaksinasi, ...$params_vaksinasi);
$stmt_vaksinasi->execute();
$vaksinasi_data = $stmt_vaksinasi->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_anak = $GLOBALS['db']->prepare($query_anak);
if (!empty($params_anak)) $stmt_anak->bind_param($types_anak, ...$params_anak);
$stmt_anak->execute();
$anak_data = $stmt_anak->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// 4. FILTER USIA & STATISTIK
// ============================================================
$filtered_vaksinasi_data = $vaksinasi_data;
if ($filter_usia_min !== null || $filter_usia_max !== null) {
    $filtered_vaksinasi_data = array_filter($vaksinasi_data, function($vaksin) use ($filter_usia_min, $filter_usia_max) {
        $usia = (int)$vaksin['usia_bulan'];
        if ($filter_usia_min !== null && $usia < $filter_usia_min) return false;
        if ($filter_usia_max !== null && $usia > $filter_usia_max) return false;
        return true;
    });
    $filtered_vaksinasi_data = array_values($filtered_vaksinasi_data);
}

$total_vaksinasi = count($filtered_vaksinasi_data);
$total_anak = count($anak_data);

$jenis_kelamin_stats = ['L' => 0, 'P' => 0];
foreach ($anak_data as $anak) {
    if (isset($jenis_kelamin_stats[$anak['jenis_kelamin']])) $jenis_kelamin_stats[$anak['jenis_kelamin']]++;
}

$vaksin_stats = [];
foreach ($filtered_vaksinasi_data as $vaksin) {
    $vaksin_name = $vaksin['nama_vaksin'];
    if (!isset($vaksin_stats[$vaksin_name])) $vaksin_stats[$vaksin_name] = 0;
    $vaksin_stats[$vaksin_name]++;
}

$bulan_stats = [];
foreach ($filtered_vaksinasi_data as $vaksin) {
    $bulan = date('m', strtotime($vaksin['tanggal_vaksinasi']));
    $tahun = date('Y', strtotime($vaksin['tanggal_vaksinasi']));
    $key = $tahun . '-' . $bulan;
    if (!isset($bulan_stats[$key])) $bulan_stats[$key] = 0;
    $bulan_stats[$key]++;
}

$kecamatan_stats = [];
foreach ($anak_data as $anak) {
    $kecamatan = $anak['kecamatan'] ?: 'Tidak diketahui';
    if (!isset($kecamatan_stats[$kecamatan])) $kecamatan_stats[$kecamatan] = 0;
    $kecamatan_stats[$kecamatan]++;
}

// ============================================================
// 5. EXPORT HANDLING (Pindahkan dari View ke atas!)
// ============================================================
if ($export === 'excel') {
    exportLaporanToExcel($filtered_vaksinasi_data);
    exit();
}
if ($export === 'pdf') {
    exportToPDF($filtered_vaksinasi_data, $filter_tahun, $filter_bulan);
    exit();
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ============================================================ -->
<!-- HTML DIMULAI DARI SINI -->
<!-- ============================================================ -->

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Laporan Vaksinasi</h4>
                        <p class="text-muted mb-0">Analisis dan statistik data vaksinasi anak</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>">
                                <i class="fas fa-file-excel me-2 text-success"></i>Excel
                            </a></li>
                            <li><a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>">
                                <i class="fas fa-file-pdf me-2 text-danger"></i>PDF
                            </a></li>
                            <li><a class="dropdown-item" href="cetak.php?<?= http_build_query($_GET) ?>" target="_blank">
                                <i class="fas fa-print me-2 text-info"></i>Print / Cetak
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan</h5></div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Tahun -->
                        <div class="col-md-2">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select class="form-select" id="tahun" name="tahun">
                                <option value="">Semua Tahun</option>
                                <?php for($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                                <option value="<?= $year ?>" <?= ($filter_tahun == $year) ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <!-- Bulan -->
                        <div class="col-md-2">
                            <label for="bulan" class="form-label">Bulan</label>
                            <select class="form-select" id="bulan" name="bulan">
                                <option value="">Semua Bulan</option>
                                <?php 
                                $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                foreach($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ($filter_bulan == $num) ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Kecamatan -->
                        <div class="col-md-2">
                            <label for="kecamatan" class="form-label">Kecamatan</label>
                            <select class="form-select" id="kecamatan" name="kecamatan">
                                <option value="">Semua Kecamatan</option>
                                <?php foreach($kecamatan_list as $kec): ?>
                                <option value="<?= e($kec['kecamatan']) ?>" <?= ($filter_kecamatan == $kec['kecamatan']) ? 'selected' : '' ?>><?= e($kec['kecamatan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Vaksin -->
                        <div class="col-md-2">
                            <label for="vaksin" class="form-label">Jenis Vaksin</label>
                            <select class="form-select" id="vaksin" name="vaksin">
                                <option value="">Semua Vaksin</option>
                                <?php foreach($jenis_vaksin_list as $vaksin): ?>
                                <option value="<?= $vaksin['id'] ?>" <?= ($filter_vaksin == $vaksin['id']) ? 'selected' : '' ?>><?= e($vaksin['nama_vaksin']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- JK -->
                        <div class="col-md-2">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                                <option value="">Semua</option>
                                <option value="L" <?= ($filter_jenis_kelamin === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($filter_jenis_kelamin === 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <!-- Petugas -->
                        <div class="col-md-2">
                            <label for="petugas" class="form-label">Petugas</label>
                            <select class="form-select" id="petugas" name="petugas">
                                <option value="">Semua Petugas</option>
                                <?php foreach($petugas_list as $petugas): ?>
                                <option value="<?= $petugas['id'] ?>" <?= ($filter_petugas == $petugas['id']) ? 'selected' : '' ?>><?= e($petugas['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Usia Min & Max -->
                        <div class="col-md-2">
                            <label for="usia_min" class="form-label">Usia Min (bln)</label>
                            <input type="number" class="form-control" id="usia_min" name="usia_min" value="<?= e($filter_usia_min) ?>" min="0" max="60" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label for="usia_max" class="form-label">Usia Max (bln)</label>
                            <input type="number" class="form-control" id="usia_max" name="usia_max" value="<?= e($filter_usia_max) ?>" min="0" max="60" placeholder="60">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 w-100">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-chart-bar me-2"></i>Tampilkan</button>
                                <a href="index.php" class="btn btn-secondary"><i class="fas fa-redo me-2"></i>Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="mb-0">Total Vaksinasi</h6><div class="number"><?= number_format($total_vaksinasi) ?></div></div>
                    <i class="fas fa-syringe"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="mb-0">Total Anak</h6><div class="number"><?= number_format($total_anak) ?></div></div>
                    <i class="fas fa-child"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="mb-0">Laki-laki</h6><div class="number"><?= number_format($jenis_kelamin_stats['L']) ?></div></div>
                    <i class="fas fa-male"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="mb-0">Perempuan</h6><div class="number"><?= number_format($jenis_kelamin_stats['P']) ?></div></div>
                    <i class="fas fa-female"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if(empty($vaksinasi_data)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Tidak ada data vaksinasi untuk filter yang dipilih.</div>
    <?php else: ?>
        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Jenis Vaksin</h5></div>
                    <div class="card-body">
                        <?php if(!empty($vaksin_stats)): ?>
                            <div style="position: relative; height: 300px;"><canvas id="vaksinChart"></canvas></div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted"><i class="fas fa-chart-pie fa-2x mb-3"></i><p>Tidak ada data untuk chart</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Trend Bulanan</h5></div>
                    <div class="card-body">
                        <?php if(!empty($bulan_stats)): ?>
                            <div style="position: relative; height: 300px;"><canvas id="monthlyChart"></canvas></div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted"><i class="fas fa-chart-line fa-2x mb-3"></i><p>Tidak ada data untuk chart</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="row mb-4">
            <!-- Top Kecamatan -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-map-marker-alt me-2"></i>Statistik per Kecamatan</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Kecamatan</th><th>Jumlah Anak</th><th>Persentase</th></tr></thead>
                                <tbody>
                                    <?php 
                                    arsort($kecamatan_stats);
                                    $top_kecamatan = array_slice($kecamatan_stats, 0, 10, true);
                                    foreach($top_kecamatan as $kecamatan => $count): 
                                        $percentage = $total_anak > 0 ? round(($count / $total_anak) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?= e($kecamatan) ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= $percentage ?>%</div>
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
            
            <!-- Top Vaksin -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-syringe me-2"></i>Vaksin Terbanyak Diberikan</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Jenis Vaksin</th><th>Jumlah</th><th>Persentase</th></tr></thead>
                                <tbody>
                                    <?php 
                                    arsort($vaksin_stats);
                                    $top_vaksin = array_slice($vaksin_stats, 0, 10, true);
                                    foreach($top_vaksin as $vaksin => $count): 
                                        $percentage = $total_vaksinasi > 0 ? round(($count / $total_vaksinasi) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?= e($vaksin) ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= $percentage ?>%</div>
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
    <?php endif; ?>

    <!-- Detailed Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-table me-2"></i>Detail Data Vaksinasi</h5>
                    <span class="badge bg-primary">Total: <?= number_format(count($filtered_vaksinasi_data)) ?> data</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>#</th><th>Tanggal</th><th>Nama Anak</th><th>NIK</th>
                                    <th>TTL</th><th>Vaksin</th><th>Usia</th><th>Kecamatan</th><th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($filtered_vaksinasi_data)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data vaksinasi</td></tr>
                                <?php else: ?>
                                <?php foreach($filtered_vaksinasi_data as $index => $vaksin): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= date('d/m/Y', strtotime($vaksin['tanggal_vaksinasi'])) ?></td>
                                    <td>
                                        <strong><?= e($vaksin['nama_anak']) ?></strong><br>
                                        <small class="text-muted"><?= $vaksin['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></small>
                                    </td>
                                    <td><?= e($vaksin['nik']) ?></td>
                                    <td><small><?= date('d/m/Y', strtotime($vaksin['tanggal_lahir'])) ?></small></td>
                                    <td>
                                        <span class="badge bg-info"><?= e($vaksin['kode_vaksin']) ?></span><br>
                                        <small><?= e($vaksin['nama_vaksin']) ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $vaksin['usia_bulan'] ?> bln</span></td>
                                    <td><small><?= e($vaksin['kecamatan'] ?: '-') ?></small></td>
                                    <td><small><?= e($vaksin['nama_petugas'] ?: '-') ?></small></td>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
window.addEventListener('load', function () {
    // Vaksin Pie Chart
    const vaksinLabels = <?= json_encode(array_keys($vaksin_stats), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const vaksinData = <?= json_encode(array_values($vaksin_stats), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    
    const vaksinCanvas = document.getElementById('vaksinChart');
    if (vaksinCanvas && vaksinLabels.length > 0) {
        new Chart(vaksinCanvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: vaksinLabels,
                datasets: [{
                    data: vaksinData,
                    backgroundColor: vaksinLabels.map((_, i) => `hsl(${i * (360 / vaksinLabels.length)}, 70%, 60%)`),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { usePointStyle: true } } }
            }
        });
    }

    // Monthly Line Chart
    const monthlyData = <?= json_encode($bulan_stats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const monthlyLabels = [];
    const monthlyValues = [];

    const sortedMonths = Object.keys(monthlyData).sort();
    sortedMonths.forEach(key => {
        const [year, month] = key.split('-');
        monthlyLabels.push(`${months[parseInt(month) - 1]} ${year}`);
        monthlyValues.push(monthlyData[key]);
    });

    const monthlyCanvas = document.getElementById('monthlyChart');
    if (monthlyCanvas && monthlyLabels.length > 0) {
        new Chart(monthlyCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Jumlah Vaksinasi',
                    data: monthlyValues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2, fill: true, tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>