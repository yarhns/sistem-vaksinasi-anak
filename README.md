# 🩺 Sistem Informasi Vaksinasi Anak

Sistem Informasi Manajemen Vaksinasi Anak berbasis web yang dirancang untuk memodernisasi proses pencatatan dan pemantauan imunisasi anak. Aplikasi ini dibangun menggunakan **PHP Native** dengan arsitektur **Model-View-Controller (MVC)** dan database **MySQL**.

Proyek ini dikembangkan sebagai portofolio pengembangan perangkat lunak, dengan fokus pada keamanan aplikasi web, desain UI/UX modern, serta pengelolaan data vaksinasi secara terstruktur.

## ✨ Fitur Utama

- 🔐 **Autentikasi & Otorisasi Aman** — Sistem login menggunakan password hashing (Bcrypt), pencegahan Session Fixation, serta pembatasan hak akses berdasarkan peran (Admin dan Petugas).
- 📊 **Dashboard Interaktif** — Menampilkan statistik data anak, informasi vaksinasi terbaru, serta visualisasi data menggunakan Chart.js.
- 👶 **Manajemen Data Anak** — Fitur CRUD (Create, Read, Update, Delete) untuk mengelola data anak beserta informasi orang tua.
- 💉 **Manajemen Vaksinasi** — Pencatatan riwayat vaksinasi, penghitungan usia anak secara otomatis saat vaksinasi, serta pelacakan petugas yang menangani.
- 📑 **Laporan Dinamis** — Menyediakan filter data berdasarkan tahun, bulan, jenis vaksin, dan kecamatan. Dilengkapi fitur ekspor Excel dan cetak PDF.
- 🛡️ **Keamanan Aplikasi** — Perlindungan terhadap SQL Injection menggunakan Prepared Statements, pencegahan XSS melalui output escaping, serta CSRF Token pada setiap form.

## 🛠️ Teknologi yang Digunakan

| Kategori | Teknologi |
|---|---|
| Backend | PHP 8.x (Native PHP dengan arsitektur MVC) |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, Bootstrap 5 |
| JavaScript | JavaScript, jQuery |
| Visualisasi Data | Chart.js |
| Notifikasi | SweetAlert2 |
| Tabel Data | DataTables |
| Date Picker | Flatpickr |
| Typography | Plus Jakarta Sans (Google Fonts) |

## 📸 Tampilan Aplikasi

Berikut beberapa tampilan antarmuka Sistem Informasi Vaksinasi Anak.

### 1. Halaman Login

Tampilan login dengan konsep split-screen modern dan ilustrasi bertema medis.

![Halaman Login](screenshots/login.png)

### 2. Dashboard Utama

Menampilkan statistik data, grafik tren vaksinasi, dan informasi data terbaru.

![Dashboard](screenshots/dashboard.png)

### 3. Manajemen Data Anak

Menampilkan tabel data anak yang dilengkapi fitur pencarian dan pengelolaan data melalui operasi CRUD.

![Data Anak](screenshots/data-anak.png)

### 4. Laporan Vaksinasi

Menampilkan laporan vaksinasi dengan filter dinamis, grafik distribusi, serta fitur ekspor laporan.

![Laporan Vaksinasi](screenshots/laporan.png)

## 🚀 Cara Menjalankan Proyek Secara Lokal

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di komputer menggunakan XAMPP.

### Prasyarat

Pastikan perangkat telah memiliki:

- PHP 8.x.
- MySQL atau MariaDB.
- XAMPP.
