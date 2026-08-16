<?php
// dashboard-pelanggan.php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

// Get statistics for the dashboard
$stats = [
    'total' => 0,
    'pending' => 0,
    'shipping' => 0,
    'delivered' => 0
];

// Get total packages count
$stmt_total = mysqli_prepare($db, 'SELECT COUNT(*) as total FROM barang WHERE id_pengirim = ?');
mysqli_stmt_bind_param($stmt_total, 'i', $user['id']);
mysqli_stmt_execute($stmt_total);
$result_total = mysqli_stmt_get_result($stmt_total);
$stats['total'] = mysqli_fetch_assoc($result_total)['total'] ?? 0;

// Get pending packages
$stmt_pending = mysqli_prepare($db, 'SELECT COUNT(*) as total FROM barang WHERE id_pengirim = ? AND status = "belum_dikirim"');
mysqli_stmt_bind_param($stmt_pending, 'i', $user['id']);
mysqli_stmt_execute($stmt_pending);
$result_pending = mysqli_stmt_get_result($stmt_pending);
$stats['pending'] = mysqli_fetch_assoc($result_pending)['total'] ?? 0;

// Get shipping packages
$stmt_shipping = mysqli_prepare($db, 'SELECT COUNT(*) as total FROM barang WHERE id_pengirim = ? AND status = "sedang_dikirim"');
mysqli_stmt_bind_param($stmt_shipping, 'i', $user['id']);
mysqli_stmt_execute($stmt_shipping);
$result_shipping = mysqli_stmt_get_result($stmt_shipping);
$stats['shipping'] = mysqli_fetch_assoc($result_shipping)['total'] ?? 0;

// Get delivered packages
$stmt_delivered = mysqli_prepare($db, 'SELECT COUNT(*) as total FROM barang WHERE id_pengirim = ? AND status = "selesai"');
mysqli_stmt_bind_param($stmt_delivered, 'i', $user['id']);
mysqli_stmt_execute($stmt_delivered);
$result_delivered = mysqli_stmt_get_result($stmt_delivered);
$stats['delivered'] = mysqli_fetch_assoc($result_delivered)['total'] ?? 0;

// Get 3 most recent packages with kurir information
$stmt_recent = mysqli_prepare(
    $db,
    'SELECT b.*, u.nama as nama_kurir 
     FROM barang b 
     LEFT JOIN users u ON b.id_kurir = u.id
     WHERE b.id_pengirim = ? 
     ORDER BY b.id_barang DESC 
     LIMIT 3'
);
mysqli_stmt_bind_param($stmt_recent, 'i', $user['id']);
mysqli_stmt_execute($stmt_recent);
$result_recent = mysqli_stmt_get_result($stmt_recent);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Packify</title>
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
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.shipping { border-left-color: #0dcaf0; }
        .stat-card.delivered { border-left-color: #198754; }

        .quick-action-btn {
            padding: 16px 20px;
            border-radius: 12px;
            background: white;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .quick-action-btn:hover {
            background: #f8f9fa;
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .quick-action-btn .action-icon {
            font-size: 1.6rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e7f1ff;
            border-radius: 12px;
            color: #0d6efd;
        }
        .quick-action-btn .action-text {
            flex: 1;
        }
        .quick-action-btn .action-text small {
            display: block;
            color: #6c757d;
            font-size: 0.8rem;
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
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-4">
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
                    <h2>Halo, <?= htmlspecialchars($user['nama'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8') ?>! 👋</h2>
                    <p>Selamat datang di dashboard Packify. Kelola semua paket pengiriman Anda dengan mudah.</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $stats['total'] ?></div>
                            <div class="stat-label">Total Paket</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-box"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card pending">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $stats['pending'] ?></div>
                            <div class="stat-label">Menunggu Kirim</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card shipping">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $stats['shipping'] ?></div>
                            <div class="stat-label">Sedang Dikirim</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card delivered">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $stats['delivered'] ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a href="package.php" class="quick-action-btn">
                    <div class="action-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="action-text">
                        <strong>Paket Saya</strong>
                        <small>Lihat semua paket pengiriman</small>
                    </div>
                    <i class="bi bi-chevron-right text-secondary"></i>
                </a>
            </div>
            <div class="col-md-4">
                <a href="barang.php" class="quick-action-btn">
                    <div class="action-icon"><i class="bi bi-plus-circle"></i></div>
                    <div class="action-text">
                        <strong>Tambah Paket</strong>
                        <small>Buat pengiriman baru</small>
                    </div>
                    <i class="bi bi-chevron-right text-secondary"></i>
                </a>
            </div>
            <div class="col-md-4">
                <a href="profile.php" class="quick-action-btn">
                    <div class="action-icon"><i class="bi bi-person"></i></div>
                    <div class="action-text">
                        <strong>Profil Saya</strong>
                        <small>Kelola informasi akun</small>
                    </div>
                    <i class="bi bi-chevron-right text-secondary"></i>
                </a>
            </div>
        </div>

        <!-- Recent Packages Table -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 section-title" style="padding-bottom: 0; margin-bottom: 0;">
                        <i class="bi bi-clock-history me-2" style="margin-bottom: 2p"></i>Paket Terbaru
                    </h5>
                    <?php if ($stats['total'] > 3): ?>
                        <a href="package.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($stats['total'] > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Penerima</th>
                                    <th>Tujuan</th>
                                    <th>Berat</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Kurir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_recent)): ?>
                                    <?php 
                                        $status_class = $row['status'] === 'belum_dikirim' ? 'secondary' : 
                                                       ($row['status'] === 'dalam_perjalanan' ? 'warning' : 
                                                       ($row['status'] === 'dikirim' ? 'success' : 'secondary'));
                                        
                                        $status_label = $row['status'] === 'belum_dikirim' ? 'Belum dikirim' : 
                                                       ($row['status'] === 'dalam_perjalanan' ? 'In transit' : 
                                                       ($row['status'] === 'dikirim' ? 'Dikirim' : $row['status']));
                                        
                                        // Display kurir name or a dash if not assigned yet
                                        $kurir_name = !empty($row['nama_kurir']) ? htmlspecialchars($row['nama_kurir'], ENT_QUOTES, 'UTF-8') : '-';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold">#<?= (int) $row['id_barang'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $row['berat_barang_kg'] ?> kg</td>
                                        <td><?= (int) $row['jumlah_barang'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $status_class ?> status-badge">
                                                <?= $status_label ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?= $kurir_name ?>
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
                        <h6 class="text-secondary">Belum ada paket</h6>
                        <p class="text-muted small">Mulai kirim paket pertama Anda sekarang!</p>
                        <a href="barang.php" class="btn btn-primary rounded-pill px-4 mt-2">
                            <i class="bi bi-plus-circle"></i> Buat Paket
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>