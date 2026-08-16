<?php
// dashboard_kurir.php
require_once __DIR__ . '/functions.php';

// Only allow couriers to access this page
$user = require_login(['kurir']);

// Handle Take Delivery action
if (isset($_GET['take']) && is_numeric($_GET['take'])) {
    $package_id = (int) $_GET['take'];
    
    // Check if package exists and is available (belum_dikirim)
    $check_stmt = mysqli_prepare($db, 'SELECT id_barang FROM barang WHERE id_barang = ? AND status = "belum_dikirim"');
    mysqli_stmt_bind_param($check_stmt, 'i', $package_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update package status and assign courier
        $update_stmt = mysqli_prepare($db, 'UPDATE barang SET status = "dalam_perjalanan", id_kurir = ? WHERE id_barang = ?');
        mysqli_stmt_bind_param($update_stmt, 'ii', $user['id'], $package_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            set_flash('success', 'Paket berhasil diambil!');
        } else {
            set_flash('error', 'Gagal mengambil paket. Silakan coba lagi.');
        }
        mysqli_stmt_close($update_stmt);
    } else {
        set_flash('error', 'Paket tidak tersedia atau sudah diambil.');
    }
    mysqli_stmt_close($check_stmt);
    
    // Redirect to refresh the page
    header('Location: dashboard-kurir.php');
    exit;
}

// Get available packages (belum_dikirim)
$stmt_available = mysqli_prepare(
    $db,
    'SELECT b.*, u.nama as nama_pengirim 
     FROM barang b 
     LEFT JOIN users u ON b.id_pengirim = u.id
     WHERE b.status = "belum_dikirim" 
     ORDER BY b.id_barang ASC'
);
mysqli_stmt_execute($stmt_available);
$result_available = mysqli_stmt_get_result($stmt_available);
$available_count = mysqli_num_rows($result_available);

// Reset result pointer to use it again
mysqli_stmt_execute($stmt_available);
$result_available = mysqli_stmt_get_result($stmt_available);

// Get courier's recent deliveries (3 most recent packages taken by this courier)
$stmt_recent = mysqli_prepare(
    $db,
    'SELECT b.*, u.nama as nama_pengirim 
     FROM barang b 
     LEFT JOIN users u ON b.id_pengirim = u.id
     WHERE b.id_kurir = ? AND b.status != "belum_dikirim"
     ORDER BY b.id_barang DESC 
     LIMIT 3'
);
mysqli_stmt_bind_param($stmt_recent, 'i', $user['id']);
mysqli_stmt_execute($stmt_recent);
$result_recent = mysqli_stmt_get_result($stmt_recent);

// Get total deliveries count for this courier
$stmt_total_deliveries = mysqli_prepare(
    $db,
    'SELECT COUNT(*) as total FROM barang WHERE id_kurir = ?'
);
mysqli_stmt_bind_param($stmt_total_deliveries, 'i', $user['id']);
mysqli_stmt_execute($stmt_total_deliveries);
$result_total_deliveries = mysqli_stmt_get_result($stmt_total_deliveries);
$total_deliveries = mysqli_fetch_assoc($result_total_deliveries)['total'] ?? 0;

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Kurir - Packify</title>
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
        .stat-card.available { border-left-color: #0d6efd; }
        .stat-card.deliveries { border-left-color: #198754; }

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

        .btn-take {
            transition: all 0.2s ease;
        }
        .btn-take:hover {
            transform: scale(1.05);
        }

        .empty-state {
            padding: 40px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
        }
        
        .package-card {
            transition: all 0.2s ease;
        }
        .package-card:hover {
            background-color: #f8f9fa;
        }

        .container-custom {
            padding-top: 40px;
            padding-bottom: 40px;
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
                    <h2>Halo, <?= htmlspecialchars($user['nama'] ?? 'Kurir', ENT_QUOTES, 'UTF-8') ?>! 🚚</h2>
                    <p>Dashboard kurir Packify. Lihat paket tersedia dan kelola pengiriman Anda.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-light text-primary rounded-pill px-4 py-2">
                        <i class="bi bi-person-badge"></i> Kurir
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card available">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $available_count ?></div>
                            <div class="stat-label">Paket Tersedia</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card deliveries">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $total_deliveries ?></div>
                            <div class="stat-label">Total Pengiriman Saya</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Packages Table -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 section-title" style="padding-bottom: 0; margin-bottom: 0;">
                        <i class="bi bi-box-seam me-2"></i>Paket Tersedia
                    </h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="bi bi-box"></i> <?= $available_count ?> paket
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($available_count > 0): ?>
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
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_available)): ?>
                                    <tr class="package-card">
                                        <td class="ps-4 fw-semibold">#<?= (int) $row['id_barang'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengirim'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $row['berat_barang_kg'] ?> kg</td>
                                        <td><?= (int) $row['jumlah_barang'] ?></td>
                                        <td class="text-end pe-4">
                                            <a href="?take=<?= (int) $row['id_barang'] ?>" 
                                               class="btn btn-success btn-sm btn-take rounded-pill px-3"
                                               onclick="return confirm('Ambil paket #<?= (int) $row['id_barang'] ?> untuk dikirim?')">
                                                <i class="bi bi-check-circle"></i> Take
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-box-seam" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h6 class="text-secondary">Tidak ada paket tersedia</h6>
                        <p class="text-muted small">Belum ada paket yang menunggu untuk diambil.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Deliveries Section -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 section-title" style="padding-bottom: 0; margin-bottom: 0;">
                        <i class="bi bi-clock-history me-2"></i>Pengiriman Terbaru
                    </h5>
                    <div>
                        <?php if ($total_deliveries > 0): ?>
                            <a href="paket-kurir.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-2">
                                <i class="bi bi-list-ul"></i> My Deliveries
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-secondary rounded-pill px-3 py-2">
                            <i class="bi bi-clock"></i> 3 terbaru
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php 
                $recent_count = mysqli_num_rows($result_recent);
                if ($recent_count > 0): 
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Pengirim</th>
                                    <th>Penerima</th>
                                    <th>Tujuan</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Kurir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_recent)): ?>
                                    <?php 
                                        $status_class = $row['status'] === 'dalam_perjalanan' ? 'warning' : 
                                                       ($row['status'] === 'dikirim' ? 'success' : 'secondary');
                                        $status_label = $row['status'] === 'dalam_perjalanan' ? 'In transit' : 
                                                       ($row['status'] === 'dikirim' ? 'Dikirim' : $row['status']);
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold">#<?= (int) $row['id_barang'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengirim'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $status_class ?> status-badge">
                                                <?= $status_label ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-truck" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h6 class="text-secondary">Belum ada pengiriman</h6>
                        <p class="text-muted small">Ambil paket untuk memulai pengiriman Anda!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>