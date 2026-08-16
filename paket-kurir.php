<?php
// paket-kurir.php
require_once __DIR__ . '/functions.php';

// Only allow couriers to access this page
$user = require_login(['kurir']);

// Handle Complete Delivery action (green button)
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $package_id = (int) $_GET['complete'];
    
    // Check if package exists and belongs to this courier and is in transit
    $check_stmt = mysqli_prepare($db, 'SELECT id_barang FROM barang WHERE id_barang = ? AND id_kurir = ? AND status = "dalam_perjalanan"');
    mysqli_stmt_bind_param($check_stmt, 'ii', $package_id, $user['id']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update package status to "dikirim"
        $update_stmt = mysqli_prepare($db, 'UPDATE barang SET status = "dikirim" WHERE id_barang = ?');
        mysqli_stmt_bind_param($update_stmt, 'i', $package_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            set_flash('success', 'Paket #' . $package_id . ' berhasil diselesaikan!');
        } else {
            set_flash('error', 'Gagal menyelesaikan paket. Silakan coba lagi.');
        }
        mysqli_stmt_close($update_stmt);
    } else {
        set_flash('error', 'Paket tidak ditemukan atau tidak dapat diselesaikan.');
    }
    mysqli_stmt_close($check_stmt);
    
    // Redirect to refresh the page
    header('Location: paket-kurir.php');
    exit;
}

// Handle Cancel Delivery action (red button)
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $package_id = (int) $_GET['cancel'];
    
    // Check if package exists and belongs to this courier and is in transit
    $check_stmt = mysqli_prepare($db, 'SELECT id_barang FROM barang WHERE id_barang = ? AND id_kurir = ? AND status = "dalam_perjalanan"');
    mysqli_stmt_bind_param($check_stmt, 'ii', $package_id, $user['id']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update package: remove courier and set status back to "belum_dikirim"
        $update_stmt = mysqli_prepare($db, 'UPDATE barang SET status = "belum_dikirim", id_kurir = NULL WHERE id_barang = ?');
        mysqli_stmt_bind_param($update_stmt, 'i', $package_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            set_flash('success', 'Pengiriman paket #' . $package_id . ' berhasil dibatalkan.');
        } else {
            set_flash('error', 'Gagal membatalkan pengiriman. Silakan coba lagi.');
        }
        mysqli_stmt_close($update_stmt);
    } else {
        set_flash('error', 'Paket tidak ditemukan atau tidak dapat dibatalkan.');
    }
    mysqli_stmt_close($check_stmt);
    
    // Redirect to refresh the page
    header('Location: paket-kurir.php');
    exit;
}

// Get all packages taken by this courier (status: dalam_perjalanan or dikirim)
$stmt_deliveries = mysqli_prepare(
    $db,
    'SELECT b.*, u.nama as nama_pengirim 
     FROM barang b 
     LEFT JOIN users u ON b.id_pengirim = u.id
     WHERE b.id_kurir = ? AND (b.status = "dalam_perjalanan" OR b.status = "dikirim")
     ORDER BY b.id_barang DESC'
);
mysqli_stmt_bind_param($stmt_deliveries, 'i', $user['id']);
mysqli_stmt_execute($stmt_deliveries);
$result_deliveries = mysqli_stmt_get_result($stmt_deliveries);
$delivery_count = mysqli_num_rows($result_deliveries);

// Reset result pointer to use it again
mysqli_stmt_execute($stmt_deliveries);
$result_deliveries = mysqli_stmt_get_result($stmt_deliveries);

// Get statistics
$stmt_in_transit = mysqli_prepare(
    $db,
    'SELECT COUNT(*) as total FROM barang WHERE id_kurir = ? AND status = "dalam_perjalanan"'
);
mysqli_stmt_bind_param($stmt_in_transit, 'i', $user['id']);
mysqli_stmt_execute($stmt_in_transit);
$result_in_transit = mysqli_stmt_get_result($stmt_in_transit);
$in_transit_count = mysqli_fetch_assoc($result_in_transit)['total'] ?? 0;

$stmt_completed = mysqli_prepare(
    $db,
    'SELECT COUNT(*) as total FROM barang WHERE id_kurir = ? AND status = "dikirim"'
);
mysqli_stmt_bind_param($stmt_completed, 'i', $user['id']);
mysqli_stmt_execute($stmt_completed);
$result_completed = mysqli_stmt_get_result($stmt_completed);
$completed_count = mysqli_fetch_assoc($result_completed)['total'] ?? 0;

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Deliveries - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Box.css">
    <style>
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            border-left: 4px solid #0d6efd;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 8px 0 4px 0;
        }
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .stat-card.in-transit { border-left-color: #ffc107; }
        .stat-card.completed { border-left-color: #198754; }
        .stat-card.total { border-left-color: #0d6efd; }

        .welcome-banner {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 16px;
            padding: 32px 40px;
            margin-bottom: 32px;
        }
        .welcome-banner h2 {
            font-weight: 700;
        }
        .welcome-banner p {
            opacity: 0.85;
            margin-bottom: 0;
        }

        .section-title {
            position: relative;
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: #0d6efd;
            border-radius: 4px;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .btn-action {
            transition: all 0.2s ease;
            min-width: 100px;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }

        .delivery-row {
            transition: all 0.2s ease;
        }
        .delivery-row:hover {
            background-color: #f8f9fa;
        }

        .container-custom {
            padding-top: 15px;
            padding-bottom: 15px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container container-custom mt-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>My Deliveries, <?= htmlspecialchars($user['nama'] ?? 'Kurir', ENT_QUOTES, 'UTF-8') ?>! 📦</h2>
                    <p>Kelola semua pengiriman yang sedang Anda tangani.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="dashboard_kurir.php" class="btn btn-light btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card total">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $delivery_count ?></div>
                            <div class="stat-label">Total Pengiriman</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card in-transit">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $in_transit_count ?></div>
                            <div class="stat-label">Dalam Perjalanan</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card completed">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $completed_count ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deliveries Table -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 section-title" style="padding-bottom: 0; margin-bottom: 0;">
                        <i class="bi bi-list-ul me-2"></i>Daftar Pengiriman
                    </h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="bi bi-box"></i> <?= $delivery_count ?> paket
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($delivery_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Pengirim</th>
                                    <th>Penerima</th>
                                    <th>Tujuan</th>
                                    <th>Berat</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_deliveries)): ?>
                                    <?php 
                                        $status_class = $row['status'] === 'dalam_perjalanan' ? 'warning' : 'success';
                                        $status_label = $row['status'] === 'dalam_perjalanan' ? 'In transit' : 'Dikirim';
                                        $is_in_transit = $row['status'] === 'dalam_perjalanan';
                                    ?>
                                    <tr class="delivery-row">
                                        <td class="ps-4 fw-semibold">#<?= (int) $row['id_barang'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengirim'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $row['berat_barang_kg'] ?> kg</td>
                                        <td><?= (int) $row['jumlah_barang'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $status_class ?> status-badge">
                                                <?= $status_label ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-4">
                                            <?php if ($is_in_transit): ?>
                                                <a href="?complete=<?= (int) $row['id_barang'] ?>" 
                                                   class="btn btn-success btn-sm btn-action rounded-pill px-3 me-1"
                                                   onclick="return confirm('Selesaikan pengiriman paket #<?= (int) $row['id_barang'] ?>?')">
                                                    <i class="bi bi-check-circle"></i> Selesaikan
                                                </a>
                                                <a href="?cancel=<?= (int) $row['id_barang'] ?>" 
                                                   class="btn btn-danger btn-sm btn-action rounded-pill px-3"
                                                   onclick="return confirm('Batalkan pengiriman paket #<?= (int) $row['id_barang'] ?>?')">
                                                    <i class="bi bi-x-circle"></i> Batalkan
                                                </a>
                                            <?php else: ?>
                                                <span class="text-success">
                                                    <i class="bi bi-check-circle-fill"></i> Selesai
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h6 class="text-secondary">Belum ada pengiriman</h6>
                        <p class="text-muted small">Anda belum mengambil paket apapun. Kembali ke dashboard untuk mengambil paket.</p>
                        <a href="dashboard_kurir.php" class="btn btn-primary rounded-pill px-4 mt-2">
                            <i class="bi bi-box-seam"></i> Lihat Paket Tersedia
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>