<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packify - Pengiriman Paket</title>
    <!-- Memanggil CSS Bootstrap 5.3 dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Packify</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Log in</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-primary px-4" href="register.php">Sign up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container mt-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold text-dark mb-3">Kirim Paket Mu Sekarang!</h1>
                <p class="lead text-secondary mb-4">
                    Packify merupakan salah satu website jasa antar paket di Indonesia. Kami mengutamakan pengalaman dan kenyamanan pelanggan dengan fitur lengkap dan profesional.
                </p>
                <a href="login.php" class="btn btn-outline-dark btn-lg px-5">Kirim Paket</a>
            </div>
            
            <div class="col-lg-6 text-center">
                <!-- Panel Indikator Server & Database -->
                <div class="card shadow-sm p-4 border-0 rounded-4">
                    <h4 class="text-dark fw-bold mb-3">Status Infrastruktur</h4>
                    <?php
                        // Memanggil file koneksi untuk testing
                        include "service/database.php";
                        if ($db) {
                            echo '<div class="alert alert-success fw-semibold">Koneksi Database MySQL Berhasil! 🚀</div>';
                        }
                    ?>
                    <p class="text-muted small mt-2 mb-0">Server PHP Native & MySQL via Docker siap digunakan oleh tim.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Memanggil JS Bootstrap dari CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>