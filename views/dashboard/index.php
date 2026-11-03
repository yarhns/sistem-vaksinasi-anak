<?php
/**
 * Dashboard.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$current_page = 'dashboard';
$page_title   = 'Dashboard';

require_once __DIR__ . '/../../models/Anak.php';
require_once __DIR__ . '/../../models/Vaksinasi.php';
require_once __DIR__ . '/../../models/Vaksin.php';

$anakModel      = new Anak();
$vaksinasiModel = new Vaksinasi();
$vaksinModel    = new Vaksin();

$db = $GLOBALS['db'];

// ============================================================
// STATISTIK UTAMA
// ============================================================
$totalAnak      = $anakModel->countAll();
$totalVaksinasi = $vaksinasiModel->countAll();

// Total user (prepared)
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM users");
$stmt->execute();
$totalUsers = (int) $stmt->get_result()->fetch_assoc()['total'];

// Total jenis vaksin
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM jenis_vaksin");
$stmt->execute();
$totalVaksin = (int) $stmt->get_result()->fetch_assoc()['total'];

// Petugas aktif
$stmt = $db->prepare("SELECT COUNT(DISTINCT petugas_id) AS total FROM vaksinasi WHERE petugas_id IS NOT NULL");
$stmt->execute();
$activePetugas = (int) $stmt->get_result()->fetch_assoc()['total'];

// ============================================================
// STATISTIK HARI INI & BULAN INI
// ============================================================
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM vaksinasi WHERE tanggal_vaksinasi = ?");
$stmt->bind_param('s', $today);
$stmt->execute();
$todayVaksinasiCount = (int) $stmt->get_result()->fetch_assoc()['total'];

$currentMonth = date('Y-m');
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM vaksinasi WHERE DATE_FORMAT(tanggal_vaksinasi, '%Y-%m') = ?");
$stmt->bind_param('s', $currentMonth);
$stmt->execute();
$monthVaksinasiCount = (int) $stmt->get_result()->fetch_assoc()['total'];

// ============================================================
// CHART: BULANAN (6 BULAN TERAKHIR) — single query
// ============================================================
$monthExpr = "DATE_FORMAT(tanggal_vaksinasi, '%Y-%m')";
$startMonth = date('Y-m', strtotime('-5 months'));
$stmt = $db->prepare("
    SELECT {$monthExpr} AS bulan, COUNT(*) AS total
    FROM vaksinasi
    WHERE tanggal_vaksinasi >= ?
    GROUP BY bulan
    ORDER BY bulan
");
$startDate = $startMonth . '-01';
$stmt->bind_param('s', $startDate);
$stmt->execute();
$rowsByMonth = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $rowsByMonth[$row['bulan']] = (int) $row['total'];
}

$monthlyLabels = [];
$monthlyData   = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $monthlyLabels[] = date('M', strtotime($month . '-01'));
    $monthlyData[]   = $rowsByMonth[$month] ?? 0;
}

// ============================================================
// TOP 5 VAKSIN
// ============================================================
$stmt = $db->prepare("
    SELECT j.nama_vaksin, COUNT(v.id) AS total
    FROM vaksinasi v
    JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
    GROUP BY j.nama_vaksin
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute();
$topVaksin = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// RECENT DATA
// ============================================================
$recentAnak = $anakModel->getAll(5, 0);

$stmt = $db->prepare("
    SELECT v.*, a.nama_anak, a.nik, j.nama_vaksin, j.kode_vaksin,
           u.nama_lengkap AS nama_petugas
    FROM vaksinasi v
    JOIN anak a ON v.anak_id = a.id
    JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
    LEFT JOIN users u ON v.petugas_id = u.id
    ORDER BY v.tanggal_vaksinasi DESC
    LIMIT 5
");
$stmt->execute();
$recentVaksinasi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// ANAK USIA DINI (<= 5 tahun)
// ============================================================
$anakBirthYear = date('Y') - 5;
$stmt = $db->prepare("
    SELECT a.*,
           TIMESTAMPDIFF(MONTH, a.tanggal_lahir, CURDATE()) AS usia_bulan,
           (SELECT COUNT(*) FROM vaksinasi WHERE anak_id = a.id) AS jumlah_vaksin
    FROM anak a
    WHERE YEAR(a.tanggal_lahir) >= ?
    ORDER BY a.tanggal_lahir DESC
    LIMIT 5
");
$stmt->bind_param('i', $anakBirthYear);
$stmt->execute();
$upcomingAnak = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// RENDER
// ============================================================
include __DIR__ . '/../layouts/header.php';
?>
<div class="container-fluid p-0">

    <!-- Welcome -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-home me-2 text-primary"></i>
                        Selamat Datang, <?= e($_SESSION['nama_lengkap'] ?? 'Pengguna') ?>!
                    </h4>
                    <p class="text-muted mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?= date('l, d F Y') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0">Total Anak</h6>
            <div class="number"><?= number_format($totalAnak) ?></div>
            <small class="opacity-75">...</small>
        </div>
        <i class="fas fa-child"></i> <!-- Gunakan ikon ini -->
    </div>
</div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Vaksinasi</h6>
                        <div class="number"><?= number_format($totalVaksinasi) ?></div>
                        <small class="opacity-75">
                            Hari ini: <?= (int) $todayVaksinasiCount ?> vaksinasi
                        </small>
                    </div>
                    <i class="fas fa-syringe"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Pengguna</h6>
                        <div class="number"><?= number_format($totalUsers) ?></div>
                        <small class="opacity-75">Petugas aktif: <?= (int) $activePetugas ?></small>
                    </div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Jenis Vaksin</h6>
                        <div class="number"><?= (int) $totalVaksin ?></div>
                        <small class="opacity-75"><?= count($topVaksin) ?> jenis terbanyak</small>
                    </div>
                    <i class="fas fa-vial"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart & Top Vaksin -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Trend Vaksinasi (6 Bulan Terakhir)
                    </h5>
                    <span class="badge bg-primary">Total: <?= array_sum($monthlyData) ?></span>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Vaksin Terbanyak
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topVaksin)): ?>
                        <p class="text-muted text-center mb-0">Belum ada data vaksinasi.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topVaksin as $index => $vaksin): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                        <?= e($vaksin['nama_vaksin']) ?>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        <?= (int) $vaksin['total'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-child me-2"></i>Anak Terbaru</h5>
                    <a href="<?= e(APP_URL) ?>/views/anak/index.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Nama</th><th>Usia</th><th>JK</th></tr></thead>
                            <tbody>
                                <?php if (empty($recentAnak)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentAnak as $anak):
                                        $usia = floor((time() - strtotime($anak['tanggal_lahir'])) / (365 * 24 * 60 * 60));
                                    ?>
                                        <tr>
                                            <td><strong><?= e($anak['nama_anak']) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= (int) $usia ?> tahun</span></td>
                                            <td>
                                                <?php if ($anak['jenis_kelamin'] === 'L'): ?>
                                                    <i class="fas fa-male text-primary"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-female text-danger"></i>
                                                <?php endif; ?>
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

        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-syringe me-2"></i>Vaksinasi Terbaru</h5>
                    <a href="<?= e(APP_URL) ?>/views/vaksinasi/index.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Anak</th><th>Vaksin</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                <?php if (empty($recentVaksinasi)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentVaksinasi as $v): ?>
                                        <tr>
                                            <td><small><?= e($v['nama_anak']) ?></small></td>
                                            <td><span class="badge bg-info"><?= e($v['kode_vaksin']) ?></span></td>
                                            <td><small><?= date('d/m', strtotime($v['tanggal_vaksinasi'])) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-baby me-2"></i>Anak Usia Dini (≤5 th)</h5>
                    <span class="badge bg-warning"><?= count($upcomingAnak) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Nama</th><th>Usia</th><th>Vaksin</th></tr></thead>
                            <tbody>
                                <?php if (empty($upcomingAnak)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingAnak as $anak): ?>
                                        <tr>
                                            <td><small><?= e($anak['nama_anak']) ?></small></td>
                                            <td><span class="badge bg-info"><?= (int) $anak['usia_bulan'] ?> bln</span></td>
                                            <td>
                                                <span class="badge bg-<?= $anak['jumlah_vaksin'] > 0 ? 'success' : 'warning' ?>">
                                                    <?= (int) $anak['jumlah_vaksin'] ?>
                                                </span>
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

    <!-- Quick Actions -->
    <?php if (hasRole('admin') || hasRole('petugas')): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="<?= e(APP_URL) ?>/views/anak/create.php" class="btn btn-primary w-100 h-100 py-3">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i><br>Tambah Anak Baru
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= e(APP_URL) ?>/views/vaksinasi/create.php" class="btn btn-success w-100 h-100 py-3">
                                    <i class="fas fa-syringe fa-2x mb-2"></i><br>Input Vaksinasi
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= e(APP_URL) ?>/views/laporan/index.php" class="btn btn-info w-100 h-100 py-3">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>Lihat Laporan
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= e(APP_URL) ?>/views/anak/index.php" class="btn btn-warning w-100 h-100 py-3">
                                    <i class="fas fa-search fa-2x mb-2"></i><br>Cari Data Anak
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
window.addEventListener('load', function () {
    // Pastikan Chart.js sudah termuat
    if (typeof Chart === 'undefined') {
        console.error('Chart.js gagal dimuat.');
        return;
    }

    const canvas = document.getElementById('monthlyChart');
    if (!canvas) {
        console.error('Canvas #monthlyChart tidak ditemukan.');
        return;
    }

    const ctx = canvas.getContext('2d');
    
    // Data dari PHP
    const labels = <?= json_encode($monthlyLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const dataValues = <?= json_encode($monthlyData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Vaksinasi',
                data: dataValues,
                backgroundColor: 'rgba(79, 70, 229, 0.8)', // Warna Indigo sesuai tema
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Penting agar tidak gepeng
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1E293B',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: '#64748B' },
                    grid: { color: '#E2E8F0', borderDash: [5, 5] }
                },
                x: {
                    ticks: { color: '#64748B' },
                    grid: { display: false }
                }
            }
        }
    });
});

(function () {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            datasets: [{
                label: 'Jumlah Vaksinasi',
                data: <?= json_encode($monthlyData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>