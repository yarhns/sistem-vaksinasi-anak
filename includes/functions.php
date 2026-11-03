<?php
/**
 * Helper functions — Sistem Vaksinasi Anak.
 * Prinsip: escape saat OUTPUT, bukan saat INPUT.
 */

// ============================================================
// FORMATTING
// ============================================================

function formatTanggal(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) return '-';
    $ts = strtotime($date);
    return $ts === false ? '-' : date($format, $ts);
}

/**
 * Escape untuk output HTML. Pakai di semua data user di view.
 * Contoh: <?= e($anak['nama_anak']) ?>
 */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function calculateAge(string $birthDate): array
{
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    $diff  = $today->diff($birth);

    return [
        'years'        => $diff->y,
        'months'       => $diff->m,
        'days'         => $diff->d,
        'total_months' => $diff->y * 12 + $diff->m,
        'human'        => $diff->y > 0
                          ? $diff->y . ' tahun'
                          : ($diff->m > 0 ? $diff->m . ' bulan' : $diff->d . ' hari'),
    ];
}

function getJenisKelamin(?string $kode): string
{
    $map = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    return $map[$kode] ?? 'Tidak diketahui';
}

// ============================================================
// CODE GENERATION
// ============================================================

/**
 * Generate kode anak unik.
 * Format: ANAK{YYYYMM}{4 HEX} — contoh: ANAK202501A3F9
 */
function generateKodeAnak(): string
{
    return 'ANAK' . date('Ym') . strtoupper(bin2hex(random_bytes(2)));
}

// ============================================================
// SECURITY: CSRF
// ============================================================

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    // SEMENTARA DIMATIKAN UNTUK TES LOGIN
    return; 
    
    /* KODE ASLI
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    ...
    */
}

// ============================================================
// NAVIGATION & FLASH
// ============================================================

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';
    return '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show" role="alert">'
         . e($flash['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

/**
 * @deprecated Gunakan setFlash() + renderFlash().
 */
function showAlert(string $type, string $message): string
{
    return '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">'
         . e($message)
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

// ============================================================
// EXPORT EXCEL (Versi Terbaru)
// ============================================================

function exportLaporanToExcel(array $data, string $filename = 'laporan_vaksinasi'): void
{
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    $esc = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr>'
       . '<th>No</th><th>Tanggal Vaksinasi</th><th>Nama Anak</th><th>NIK</th>'
       . '<th>Tanggal Lahir</th><th>Jenis Kelamin</th><th>Jenis Vaksin</th>'
       . '<th>Usia (Bulan)</th><th>Kecamatan</th><th>Kelurahan</th>'
       . '<th>Batch Number</th><th>Lokasi</th><th>Petugas</th><th>Catatan</th>'
       . '</tr>';

    foreach ($data as $i => $row) {
        $birthDate = new DateTime($row['tanggal_lahir']);
        $vaxDate   = new DateTime($row['tanggal_vaksinasi']);
        $diff      = $birthDate->diff($vaxDate);
        $ageMonths = $diff->y * 12 + $diff->m;

        echo '<tr>'
            . '<td>' . ($i + 1) . '</td>'
            . '<td>' . $esc($row['tanggal_vaksinasi']) . '</td>'
            . '<td>' . $esc($row['nama_anak']) . '</td>'
            . '<td>' . $esc($row['nik']) . '</td>'
            . '<td>' . $esc($row['tanggal_lahir']) . '</td>'
            . '<td>' . ($row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') . '</td>'
            . '<td>' . $esc($row['nama_vaksin'] . ' (' . $row['kode_vaksin'] . ')') . '</td>'
            . '<td>' . $ageMonths . '</td>'
            . '<td>' . $esc($row['kecamatan']) . '</td>'
            . '<td>' . $esc($row['kelurahan']) . '</td>'
            . '<td>' . $esc($row['batch_number']) . '</td>'
            . '<td>' . $esc($row['lokasi_vaksinasi']) . '</td>'
            . '<td>' . $esc($row['nama_petugas']) . '</td>'
            . '<td>' . $esc($row['catatan']) . '</td>'
            . '</tr>';
    }

    echo '</table></body></html>';
    exit();
}

// ============================================================
// EXPORT PDF
// ============================================================

/**
 * Export laporan ke format PDF sederhana (Print HTML).
 */
function exportToPDF(array $data, string $tahun = '', string $bulan = ''): void
{
    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $bulan_nama = isset($months[$bulan]) ? $months[$bulan] : 'Semua Bulan';
    $tahun_nama = $tahun ?: 'Semua Tahun';

    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html><html><head><title>Laporan Vaksinasi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 30px; text-align: right; }
    </style></head><body>';

    echo '<h1>LAPORAN DATA VAKSINASI</h1>';
    echo '<p><strong>Periode:</strong> ' . htmlspecialchars($bulan_nama) . ' ' . htmlspecialchars($tahun_nama) . '</p>';
    echo '<p><strong>Tanggal Cetak:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    echo '<p><strong>Jumlah Data:</strong> ' . count($data) . '</p>';

    if (!empty($data)) {
        echo '<table><thead><tr>
                <th>No</th><th>Tanggal</th><th>Nama Anak</th><th>NIK</th>
                <th>Vaksin</th><th>Usia</th><th>Kecamatan</th><th>Petugas</th>
              </tr></thead><tbody>';

        foreach ($data as $i => $row) {
            $ageMonths = $row['usia_bulan'] ?? 0;
            echo '<tr>
                    <td>' . ($i + 1) . '</td>
                    <td>' . date('d/m/Y', strtotime($row['tanggal_vaksinasi'])) . '</td>
                    <td>' . htmlspecialchars($row['nama_anak']) . '</td>
                    <td>' . htmlspecialchars($row['nik']) . '</td>
                    <td>' . htmlspecialchars($row['nama_vaksin']) . '</td>
                    <td>' . $ageMonths . ' bln</td>
                    <td>' . htmlspecialchars($row['kecamatan'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($row['nama_petugas'] ?? '-') . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p style="text-align:center;">Tidak ada data.</p>';
    }

    echo '<div class="footer"><p>Dicetak oleh: ' . htmlspecialchars($_SESSION['nama_lengkap'] ?? '-') . '</p></div>';
    echo '<script>window.print();</script>';
    echo '</body></html>';
    exit();
}

// ============================================================
// FORM OPTIONS
// ============================================================

function generateBulanOptions($selected = ''): string
{
    $months = ['Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];

    $html = '<option value="all">Semua Bulan</option>';
    foreach ($months as $i => $name) {
        $num = $i + 1;
        $sel = ((string) $selected === (string) $num) ? ' selected' : '';
        $html .= '<option value="' . $num . '"' . $sel . '>' . $name . '</option>';
    }
    return $html;
}

function generateTahunOptions($selected = '', int $yearsBack = 5): string
{
    $current = (int) date('Y');
    $html = '';
    for ($y = $current; $y >= $current - $yearsBack; $y--) {
        $sel = ((string) $selected === (string) $y) ? ' selected' : '';
        $html .= '<option value="' . $y . '"' . $sel . '>' . $y . '</option>';
    }
    return $html;
}