-- Database: vaksinasi_anak
CREATE DATABASE IF NOT EXISTS vaksinasi_anak 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE vaksinasi_anak;

-- Table: users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas', 'orangtua') DEFAULT 'orangtua',
    email VARCHAR(100),
    no_hp VARCHAR(20),
    foto_profil VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: anak
CREATE TABLE anak (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_anak VARCHAR(20) UNIQUE NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama_anak VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    nama_ayah VARCHAR(100),
    nama_ibu VARCHAR(100),
    alamat TEXT,
    kelurahan VARCHAR(50),
    kecamatan VARCHAR(50),
    kabupaten VARCHAR(50),
    provinsi VARCHAR(50),
    no_hp_ortu VARCHAR(20),
    berat_lahir DECIMAL(4,2),
    panjang_lahir DECIMAL(4,2),
    golongan_darah VARCHAR(2),
    alergi TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: jenis_vaksin
CREATE TABLE jenis_vaksin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_vaksin VARCHAR(10) UNIQUE NOT NULL,
    nama_vaksin VARCHAR(100) NOT NULL,
    usia_pemberian_bulan INT NOT NULL,
    deskripsi TEXT,
    booster_ke INT DEFAULT 1,
    is_wajib BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: vaksinasi
CREATE TABLE vaksinasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    anak_id INT NOT NULL,
    jenis_vaksin_id INT NOT NULL,
    tanggal_vaksinasi DATE NOT NULL,
    usia_saat_vaksinasi_bulan INT,
    batch_number VARCHAR(50),
    lokasi_vaksinasi VARCHAR(100),
    penyelenggara VARCHAR(100),
    petugas_id INT,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE,
    FOREIGN KEY (jenis_vaksin_id) REFERENCES jenis_vaksin(id),
    FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: jadwal_vaksin
CREATE TABLE jadwal_vaksin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    anak_id INT NOT NULL,
    jenis_vaksin_id INT NOT NULL,
    tanggal_jadwal DATE NOT NULL,
    status ENUM('terjadwal', 'selesai', 'terlewat') DEFAULT 'terjadwal',
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE,
    FOREIGN KEY (jenis_vaksin_id) REFERENCES jenis_vaksin(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Sistem', 'admin', 'admin@vaksinasi.local');

-- Insert jenis vaksin dasar
INSERT INTO jenis_vaksin (kode_vaksin, nama_vaksin, usia_pemberian_bulan, booster_ke) VALUES
('HB0', 'Hepatitis B', 0, 1),
('BCG', 'BCG', 1, 1),
('POL1', 'Polio 1', 2, 1),
('DPT1', 'DPT-HB-Hib 1', 2, 1),
('POL2', 'Polio 2', 3, 1),
('DPT2', 'DPT-HB-Hib 2', 3, 1),
('POL3', 'Polio 3', 4, 1),
('DPT3', 'DPT-HB-Hib 3', 4, 1),
('IPV', 'IPV', 4, 1),
('CAM', 'Campak', 9, 1),
('MMR', 'MMR', 15, 1),
('DPT-B', 'DPT Booster', 18, 2),
('POL-B', 'Polio Booster', 18, 2),
('HepA', 'Hepatitis A', 24, 1),
('Tifoid', 'Tifoid', 24, 1);

-- Create indexes for better performance
CREATE INDEX idx_anak_nik ON anak(nik);
CREATE INDEX idx_anak_user_id ON anak(user_id);
CREATE INDEX idx_vaksinasi_anak_id ON vaksinasi(anak_id);
CREATE INDEX idx_vaksinasi_tanggal ON vaksinasi(tanggal_vaksinasi);
CREATE INDEX idx_jadwal_status ON jadwal_vaksin(status);
CREATE INDEX idx_users_username ON users(username);