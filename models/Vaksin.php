<?php
require_once __DIR__ . '/../config/database.php';

class Vaksin {
    private $conn;
    private $table = 'jenis_vaksin';

    public function __construct() {
        global $db;
        $this->conn = $db->getConnection();
    }

    public function getAllJenisVaksin() {
        $query = "SELECT * FROM $this->table ORDER BY usia_pemberian_bulan, nama_vaksin";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getByKode($kode) {
        $query = "SELECT * FROM $this->table WHERE kode_vaksin = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO $this->table (kode_vaksin, nama_vaksin, usia_pemberian_bulan, deskripsi, booster_ke, is_wajib) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssisii",
            $data['kode_vaksin'],
            $data['nama_vaksin'],
            $data['usia_pemberian_bulan'],
            $data['deskripsi'],
            $data['booster_ke'],
            $data['is_wajib']
        );
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE $this->table SET 
                  kode_vaksin = ?, 
                  nama_vaksin = ?, 
                  usia_pemberian_bulan = ?, 
                  deskripsi = ?, 
                  booster_ke = ?, 
                  is_wajib = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssisiii",
            $data['kode_vaksin'],
            $data['nama_vaksin'],
            $data['usia_pemberian_bulan'],
            $data['deskripsi'],
            $data['booster_ke'],
            $data['is_wajib'],
            $id
        );
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getRecommendedVaksin($usia_bulan) {
        $query = "SELECT * FROM $this->table 
                  WHERE usia_pemberian_bulan <= ? 
                  AND is_wajib = 1
                  ORDER BY usia_pemberian_bulan";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $usia_bulan);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM $this->table";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }
}
?>