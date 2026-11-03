<?php
require_once __DIR__ . '/../../config/config.php';

$errorRaw = $_GET['error'] ?? '';
$error = in_array($errorRaw, ['empty', 'invalid', 'inactive', 'forbidden'], true) ? $errorRaw : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css?v=2.0">
    <style>
        body {
            background-color: #F3F4F6;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .login-wrapper {
            display: flex;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
        }
        
        /* Bagian Kiri: Area Gambar */
        .login-left {
            flex: 0 0 45%; /* Proporsi gambar 45% */
            position: relative;
            background-color: #1E293B; /* Warna dasar jika gambar lambat load */
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 40px;
            color: white;
        }

        /* Mengatur tag <img> agar memenuhi area kiri */
        .login-left img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Mencegah gambar gepeng */
            z-index: 0;
        }

        /* Overlay gelap agar teks putih mudah dibaca */
        .login-left::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.2) 100%);
            z-index: 1;
        }

        .login-left-text {
            position: relative;
            z-index: 2; /* Teks berada di atas overlay */
        }

        /* Bagian Kanan: Area Form */
        .login-right {
            flex: 0 0 55%; /* Proporsi form 55% */
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-wrapper { max-width: 450px; min-height: auto; }
            .login-right { padding: 40px 30px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        
        <!-- BAGIAN KIRI (GAMBAR) -->
        <div class="login-left">
            <!-- URL Unsplash yang sudah dipastikan aktif -->
            <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?q=80&w=1000&auto=format&fit=crop" alt="Dokter dan Anak">
            
            <div class="login-left-text">
                <h2 class="fw-bold mb-2">Sistem Vaksinasi</h2>
                <p class="mb-0 opacity-75">Pantau tumbuh kembang dan jadwal imunisasi anak Anda dengan mudah, aman, dan terpercaya.</p>
            </div>
        </div>

        <!-- BAGIAN KANAN (FORM LOGIN) -->
        <div class="login-right">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background: var(--primary-color); border-radius: 12px; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);">
                    <i class="fas fa-syringe text-white" style="font-size: 1.5rem;"></i>
                </div>
                <h3 class="fw-bold" style="color: #1E293B;">Selamat Datang</h3>
                <p class="text-muted">Silakan login ke akun Anda</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                    switch ($error) {
                        case 'invalid':  echo 'Username atau password salah!'; break;
                        case 'inactive': echo 'Akun tidak aktif!'; break;
                        case 'empty':    echo 'Username dan password wajib diisi!'; break;
                        case 'forbidden':echo 'Anda tidak memiliki akses ke halaman tersebut.'; break;
                        default:         echo 'Terjadi kesalahan saat login!';
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form action="<?= e(APP_URL) ?>/index.php?action=login" method="POST" autocomplete="off">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="login">

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="username" name="username" required autofocus placeholder="Masukkan username">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Masukkan password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2" style="font-weight: 600;">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">&copy; 2026 - Dear Haens</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>