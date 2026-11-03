<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Set current page (no sidebar for print)
$current_page = 'laporan';

// Get filter parameters
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_kecamatan = $_GET['kecamatan'] ?? '';

// Build query
$query = "SELECT 
    v.*,
    a.nik,
    a.nama_anak,
    a.tanggal_lahir,
    a.jenis_kelamin,
    a.kecamatan,
    a.kelurahan,
    j.kode_vaksin,
    j.nama_vaksin,
    u.nama_lengkap as nama_petugas
FROM vaksinasi v
JOIN anak a ON v.anak_id = a.id
JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
LEFT JOIN users u ON v.petugas_id = u.id
WHERE YEAR(v.tanggal_vaksinasi) = ?";

$params = [$filter_tahun];
$types = 'i';

if (!empty($filter_bulan) && $filter_bulan != 'all') {
    $query .= " AND MONTH(v.tanggal_vaksinasi) = ?";
    $params[] = $filter_bulan;
    $types .= 'i';
}

if (!empty($filter_kecamatan)) {
    $query .= " AND a.kecamatan = ?";
    $params[] = $filter_kecamatan;
    $types .= 's';
}

$query .= " ORDER BY v.tanggal_vaksinasi, a.nama_anak";

// Execute query
$stmt = $GLOBALS['db']->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vaksinasi_data = $result->fetch_all(MYSQLI_ASSOC);

// Get bulan name
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$bulan_nama = isset($months[$filter_bulan]) ? $months[$filter_bulan] : 'Semua Bulan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Vaksinasi</title>
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 0.5cm;
            }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        .info-box {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #333;
            text-align: right;
            font-size: 11px;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        .text-center {
            text-align: center;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .print-controls {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
            font-size: 14px;
        }
        .btn-print {
            background-color: #007bff;
            color: white;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-item h3 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 12px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="btn btn-print" onclick="window.print()">
            🖨️ Cetak Laporan
        </button>
        <button class="btn btn-back" onclick="window.history.back()">
            ↩️ Kembali
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>LAPORAN DATA VAKSINASI ANAK</h1>
        <h2>Sistem Informasi Vaksinasi Anak</h2>
        <p>Periode: <?php echo $bulan_nama . ' ' . $filter_tahun; ?></p>
    </div>

    <!-- Summary Statistics -->
    <?php
    $total_vaksinasi = count($vaksinasi_data);
    
    // Calculate unique children
    $unique_children = [];
    foreach ($vaksinasi_data as $vaksin) {
        $unique_children[$vaksin['anak_id']] = true;
    }
    $total_unique_children = count($unique_children);
    
    // Calculate gender statistics
    $gender_stats = ['L' => 0, 'P' => 0];
    foreach ($vaksinasi_data as $vaksin) {
        if (isset($gender_stats[$vaksin['jenis_kelamin']])) {
            $gender_stats[$vaksin['jenis_kelamin']]++;
        }
    }
    ?>
    
    <div class="summary">
        <div class="summary-item">
            <h3><?php echo number_format($total_vaksinasi); ?></h3>
            <p>Total Vaksinasi</p>
        </div>
        <div class="summary-item" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
            <h3><?php echo number_format($total_unique_children); ?></h3>
            <p>Anak Divaksin</p>
        </div>
        <div class="summary-item" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
            <h3><?php echo number_format($gender_stats['L']); ?></h3>
            <p>Laki-laki</p>
        </div>
        <div class="summary-item" style="background: linear-gradient(135deg, #E91E63 0%, #C2185B 100%);">
            <h3><?php echo number_format($gender_stats['P']); ?></h3>
            <p>Perempuan</p>
        </div>
    </div>

    <!-- Information Box -->
    <div class="info-box">
        <p><strong>Informasi Laporan:</strong></p>
        <p>• Periode: <?php echo $bulan_nama . ' ' . $filter_tahun; ?></p>
        <p>• Kecamatan: <?php echo !empty($filter_kecamatan) ? $filter_kecamatan : 'Semua Kecamatan'; ?></p>
        <p>• Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>• Dicetak oleh: <?= e($_SESSION['nama_lengkap']) ?> (<?= e($_SESSION['role']) ?>)</p>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="30">#</th>
                <th width="80">Tanggal</th>
                <th>Nama Anak</th>
                <th width="120">NIK</th>
                <th width="100">TTL</th>
                <th width="120">Vaksin</th>
                <th width="70">Usia</th>
                <th width="100">Kecamatan</th>
                <th width="100">Petugas</th>
                <th width="80">Batch No</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($vaksinasi_data)): ?>
            <tr>
                <td colspan="10" class="no-data">
                    Tidak ada data vaksinasi untuk periode ini
                </td>
            </tr>
            <?php else: ?>
            <?php foreach($vaksinasi_data as $index => $vaksin): 
                // Calculate age
                $birth_date = new DateTime($vaksin['tanggal_lahir']);
                $vax_date = new DateTime($vaksin['tanggal_vaksinasi']);
                $interval = $birth_date->diff($vax_date);
                $age_months = $interval->y * 12 + $interval->m;
            ?>
            <tr>
                <td class="text-center"><?php echo $index + 1; ?></td>
                <td><?php echo date('d/m/y', strtotime($vaksin['tanggal_vaksinasi'])); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($vaksin['nama_anak']); ?></strong><br>
                    <small><?php echo $vaksin['jenis_kelamin'] == 'L' ? 'L' : 'P'; ?></small>
                </td>
                <td><?php echo htmlspecialchars($vaksin['nik']); ?></td>
                <td><?php echo date('d/m/y', strtotime($vaksin['tanggal_lahir'])); ?></td>
                <td>
                    <span class="badge badge-info"><?php echo htmlspecialchars($vaksin['kode_vaksin']); ?></span><br>
                    <small><?php echo htmlspecialchars(substr($vaksin['nama_vaksin'], 0, 20)); ?></small>
                </td>
                <td class="text-center">
                    <span class="badge badge-secondary"><?php echo $age_months; ?>bln</span>
                </td>
                <td><?php echo htmlspecialchars($vaksin['kecamatan'] ?: '-'); ?></td>
                <td><small><?php echo htmlspecialchars($vaksin['nama_petugas'] ?: '-'); ?></small></td>
                <td><code><?php echo htmlspecialchars(substr($vaksin['batch_number'], 0, 8) ?: '-'); ?></code></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Halaman 1 dari 1 | <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>© <?= date('Y') ?> - <?= e(APP_NAME) ?></p>
    </div>

    <script>
    // Auto print if requested
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
        window.print();
    }
    
    // After print, go back
    window.onafterprint = function() {
        setTimeout(function() {
            window.history.back();
        }, 1000);
    };
    </script>
</body>
</html>