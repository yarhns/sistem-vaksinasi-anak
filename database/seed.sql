-- Seed data awal: admin user + jenis vaksin dasar

-- Admin default (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role, email, is_active)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrator Sistem',
    'admin',
    'admin@vaksinasi.local',
    TRUE
);

-- Jenis vaksin dasar (sesuai jadwal imunisasi IDAI)
INSERT INTO jenis_vaksin (kode_vaksin, nama_vaksin, usia_pemberian_bulan, booster_ke) VALUES
('HB0',   'Hepatitis B',       0,  1),
('BCG',   'BCG',               1,  1),
('POL1',  'Polio 1',           2,  1),
('DPT1',  'DPT-HB-Hib 1',      2,  1),
('POL2',  'Polio 2',           3,  1),
('DPT2',  'DPT-HB-Hib 2',      3,  1),
('POL3',  'Polio 3',           4,  1),
('DPT3',  'DPT-HB-Hib 3',      4,  1),
('IPV',   'IPV',               4,  1),
('CAM',   'Campak',            9,  1),
('MMR',   'MMR',               15, 1),
('DPT-B', 'DPT Booster',       18, 2),
('POL-B', 'Polio Booster',     18, 2),
('HepA',  'Hepatitis A',       24, 1),
('Tifoid','Tifoid',            24, 1);