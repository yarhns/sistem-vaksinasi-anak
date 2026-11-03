<?php
require_once __DIR__ . '/../config/database.php';

class Vaksinasi {
    private $conn;
    private $table = 'vaksinasi';

    public function __construct() {
        global $db;
        $this->conn = $db->getConnection();
    }

    public function getByAnakId($anak_id) {
        $query = "SELECT v.*, j.nama_vaksin, j.kode_vaksin, u.nama_lengkap as nama_petugas
                  FROM $this->table v
                  JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
                  LEFT JOIN users u ON v.petugas_id = u.id
                  WHERE v.anak_id = ?
                  ORDER BY v.tanggal_vaksinasi DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $anak_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT v.*, a.nama_anak, a.nik, a.tanggal_lahir, j.nama_vaksin, j.kode_vaksin, u.nama_lengkap as nama_petugas
                  FROM $this->table v
                  JOIN anak a ON v.anak_id = a.id
                  JOIN jenis_vaksin j ON v.jenis_vaksin_id = j.id
                  LEFT JOIN users u ON v.petugas_id = u.id
                  WHERE v.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO $this->table (
            anak_id, jenis_vaksin_id, tanggal_vaksinasi, usia_saat_vaksinasi_bulan,
            batch_number, lokasi_vaksinasi, penyelenggara, petugas_id, catatan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Pastikan semua di-cast ke tipe yang benar
        $anak_id = (int)$data['anak_id'];
        $jenis_vaksin_id = (int)$data['jenis_vaksin_id'];
        $tanggal = $data['tanggal_vaksinasi'];
        $usia = !empty($data['usia_saat_vaksinasi_bulan']) ? (int)$data['usia_saat_vaksinasi_bulan'] : 0;
        $batch = $data['batch_number'] ?? '';
        $lokasi = $data['lokasi_vaksinasi'] ?? '';
        $penyelenggara = $data['penyelenggara'] ?? '';
        $petugas_id = (int)$data['petugas_id'];
        $catatan = $data['catatan'] ?? '';

        $stmt->bind_param(
            "iisisssis", // 2 int, 1 int, 3 string, 1 int, 1 string = 9 parameter
            $anak_id, $jenis_vaksin_id, $tanggal, $usia, 
            $batch, $lokasi, $penyelenggara, $petugas_id, $catatan
        );
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE $this->table SET 
                  anak_id = ?, jenis_vaksin_id = ?, tanggal_vaksinasi = ?, 
                  usia_saat_vaksinasi_bulan = ?, batch_number = ?, lokasi_vaksinasi = ?, 
                  penyelenggara = ?, catatan = ?, updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        $anak_id = (int)$data['anak_id'];
        $jenis_vaksin_id = (int)$data['jenis_vaksin_id'];
        $tanggal = $data['tanggal_vaksinasi'];
        $usia = !empty($data['usia_saat_vaksinasi_bulan']) ? (int)$data['usia_saat_vaksinasi_bulan'] : 0;
        $batch = $data['batch_number'] ?? '';
        $lokasi = $data['lokasi_vaksinasi'] ?? '';
        $penyelenggara = $data['penyelenggara'] ?? '';
        $catatan = $data['catatan'] ?? '';
        $id = (int)$id;

        $stmt->bind_param(
            "iisissssi", 
            $anak_id, $jenis_vaksin_id, $tanggal, $usia, 
            $batch, $lokasi, $penyelenggara, $catatan, $id
        );
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM $this->table";
        $result = $this->conn->query($query);
        return (int) $result->fetch_assoc()['total'];
    }

    // Fungsi statistik untuk halaman index
    public function getStats($bulan = '', $tahun = '') {
        $where = " WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($bulan)) {
            $where .= " AND MONTH(tanggal_vaksinasi) = ?";
            $params[] = (int)$bulan;
            $types .= 'i';
        }
        if (!empty($tahun)) {
            $where .= " AND YEAR(tanggal_vaksinasi) = ?";
            $params[] = (int)$tahun;
            $types .= 'i';
        }

        // 1. Total Vaksinasi
        $q1 = "SELECT COUNT(*) as total FROM vaksinasi" . $where;
        $stmt = $this->conn->prepare($q1);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // 2. Anak Divaksin
        $q2 = "SELECT COUNT(DISTINCT anak_id) as total FROM vaksinasi" . $where;
        $stmt = $this->conn->prepare($q2);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $anak = $stmt->get_result()->fetch_assoc()['total'];

        // 3. Jenis Vaksin
        $q3 = "SELECT COUNT(DISTINCT jenis_vaksin_id) as total FROM vaksinasi" . $where;
        $stmt = $this->conn->prepare($q3);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $vaksin = $stmt->get_result()->fetch_assoc()['total'];

        // 4. Petugas Aktif
        $q4 = "SELECT COUNT(DISTINCT petugas_id) as total FROM vaksinasi WHERE petugas_id IS NOT NULL" . str_replace("WHERE", "AND", $where);
        $stmt = $this->conn->prepare($q4);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $petugas = $stmt->get_result()->fetch_assoc()['total'];

        return [
            'total' => $total,
            'anak' => $anak,
            'vaksin' => $vaksin,
            'petugas' => $petugas
        ];
    }
}
?>