<?php
require_once __DIR__ . '/../config/database.php';

class Anak {
    private $conn;
    private $table = 'anak';

    public function __construct() {
        global $db;
        $this->conn = $db->getConnection();
    }

    public function getAll($limit = null, $offset = 0) {
        $query = "SELECT * FROM $this->table ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($query);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getByNik($nik) {
        $query = "SELECT * FROM $this->table WHERE nik = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $nik);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        // Generate kode anak unik pakai random_bytes (lebih aman dari mt_rand)
        $kode_anak = 'ANAK' . date('Ym') . strtoupper(bin2hex(random_bytes(2)));
        
        $query = "INSERT INTO $this->table (
            kode_anak, nik, nama_anak, tempat_lahir, tanggal_lahir, 
            jenis_kelamin, nama_ayah, nama_ibu, alamat, no_hp_ortu,
            kelurahan, kecamatan, kabupaten, provinsi, 
            berat_lahir, panjang_lahir, golongan_darah, alergi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        $nik = $data['nik'] ?? '';
        $nama_anak = $data['nama_anak'] ?? '';
        $tempat_lahir = $data['tempat_lahir'] ?? '';
        $tanggal_lahir = $data['tanggal_lahir'] ?? '';
        $jenis_kelamin = $data['jenis_kelamin'] ?? '';
        $nama_ayah = $data['nama_ayah'] ?? '';
        $nama_ibu = $data['nama_ibu'] ?? '';
        $alamat = $data['alamat'] ?? '';
        $no_hp_ortu = $data['no_hp_ortu'] ?? '';
        
        $kelurahan = $data['kelurahan'] ?? '';
        $kecamatan = $data['kecamatan'] ?? '';
        $kabupaten = $data['kabupaten'] ?? '';
        $provinsi = $data['provinsi'] ?? '';
        
        $berat_lahir = !empty($data['berat_lahir']) ? (float)$data['berat_lahir'] : 0;
        $panjang_lahir = !empty($data['panjang_lahir']) ? (float)$data['panjang_lahir'] : 0;
        $golongan_darah = $data['golongan_darah'] ?? '';
        $alergi = $data['alergi'] ?? '';

        // 10 string + 4 string + 2 double + 2 string = 18 huruf
        $stmt->bind_param(
            "ssssssssssssssddss",
            $kode_anak, $nik, $nama_anak, $tempat_lahir, $tanggal_lahir,
            $jenis_kelamin, $nama_ayah, $nama_ibu, $alamat, $no_hp_ortu,
            $kelurahan, $kecamatan, $kabupaten, $provinsi,
            $berat_lahir, $panjang_lahir, $golongan_darah, $alergi
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

        public function update($id, $data) {
        $query = "UPDATE $this->table SET 
                  nik = ?, nama_anak = ?, tempat_lahir = ?, tanggal_lahir = ?,
                  jenis_kelamin = ?, nama_ayah = ?, nama_ibu = ?, alamat = ?,
                  kelurahan = ?, kecamatan = ?, kabupaten = ?, provinsi = ?,
                  no_hp_ortu = ?, berat_lahir = ?, panjang_lahir = ?,
                  golongan_darah = ?, alergi = ?, updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Ambil data dan ubah semua menjadi string untuk keamanan bind_param
        $nik = $data['nik'] ?? '';
        $nama_anak = $data['nama_anak'] ?? '';
        $tempat_lahir = $data['tempat_lahir'] ?? '';
        $tanggal_lahir = $data['tanggal_lahir'] ?? '';
        $jenis_kelamin = $data['jenis_kelamin'] ?? '';
        $nama_ayah = $data['nama_ayah'] ?? '';
        $nama_ibu = $data['nama_ibu'] ?? '';
        $alamat = $data['alamat'] ?? '';
        $kelurahan = $data['kelurahan'] ?? '';
        $kecamatan = $data['kecamatan'] ?? '';
        $kabupaten = $data['kabupaten'] ?? '';
        $provinsi = $data['provinsi'] ?? '';
        $no_hp_ortu = $data['no_hp_ortu'] ?? '';
        
        // Paksa jadi string, kalau kosong isi dengan '0' atau ''
        $berat_lahir = !empty($data['berat_lahir']) ? (string)$data['berat_lahir'] : '0';
        $panjang_lahir = !empty($data['panjang_lahir']) ? (string)$data['panjang_lahir'] : '0';
        
        $golongan_darah = $data['golongan_darah'] ?? '';
        $alergi = $data['alergi'] ?? '';
        
        // Konversi ID ke integer
        $id = (int)$id;

        // Hitung: 17 variabel data + 1 ID = 18.
        // Karena semua dijadikan string, kita cukup pakai 18 huruf 's'.
        $stmt->bind_param(
            "sssssssssssssssssi", // 18 huruf 's'
            $nik, $nama_anak, $tempat_lahir, $tanggal_lahir,
            $jenis_kelamin, $nama_ayah, $nama_ibu, $alamat,
            $kelurahan, $kecamatan, $kabupaten, $provinsi,
            $no_hp_ortu, $berat_lahir, $panjang_lahir,
            $golongan_darah, $alergi, $id
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

    public function search($keyword) {
        $query = "SELECT * FROM $this->table 
                  WHERE nik LIKE ? OR nama_anak LIKE ? OR nama_ayah LIKE ? OR nama_ibu LIKE ?
                  ORDER BY created_at DESC";
        
        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>