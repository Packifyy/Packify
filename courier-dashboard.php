<?php
require_once __DIR__ . '/functions.php';

start_session_safe();

/* =====================================================
   COURIER ACCESS PROTECTION
===================================================== */
$user = current_user();

if ($user === null || ($user['role'] ?? '') !== 'kurir') {
    header('Location: login.php?role=courier');
    exit;
}

$courierId = (int) ($user['id'] ?? 0);
$name = $user['nama'] ?? 'Courier';
$firstName = explode(' ', trim($name))[0];
$initial = strtoupper(substr(trim($name), 0, 1));

/* =====================================================
   COURIER POST ACTIONS (CSRF PROTECTED)
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // 1. Ambil Paket (Take shipment)
    if ($action === 'take_shipment' || ($action === 'update_shipment_status' && ($_POST['status'] ?? '') === 'sedang_dikirim')) {
        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);

        if ($shipmentId < 1) {
            set_flash('danger', 'ID paket tidak valid.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $stmt = mysqli_prepare(
            $db,
            "UPDATE barang 
             SET status = 'sedang_dikirim', id_kurir = ? 
             WHERE id_barang = ? AND status = 'belum_dikirim' AND (id_kurir IS NULL OR id_kurir = 0)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $courierId, $shipmentId);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                set_flash('success', 'Paket PKF-' . str_pad((string)$shipmentId, 4, '0', STR_PAD_LEFT) . ' berhasil diambil.');
            } else {
                set_flash('danger', 'Paket tidak tersedia atau sudah diambil kurir lain.');
            }
        }

        header('Location: courier-dashboard.php#deliveries');
        exit;
    }

    // 2. Batalkan Pengambilan (Cancel pickup)
    if ($action === 'cancel_pickup') {
        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);

        if ($shipmentId < 1) {
            set_flash('danger', 'ID paket tidak valid.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $stmt = mysqli_prepare(
            $db,
            "UPDATE barang 
             SET status = 'belum_dikirim', id_kurir = NULL 
             WHERE id_barang = ? AND id_kurir = ? AND status = 'sedang_dikirim'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $shipmentId, $courierId);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                set_flash('success', 'Pengambilan paket PKF-' . str_pad((string)$shipmentId, 4, '0', STR_PAD_LEFT) . ' berhasil dibatalkan.');
            } else {
                set_flash('danger', 'Gagal membatalkan pengambilan paket.');
            }
        }

        header('Location: courier-dashboard.php#tasks');
        exit;
    }

    // 3. Pengiriman Selesai / Tandai Selesai (Complete shipment)
    if ($action === 'complete_shipment' || ($action === 'update_shipment_status' && ($_POST['status'] ?? '') === 'sudah_sampai')) {
        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);

        if ($shipmentId < 1) {
            set_flash('danger', 'ID paket tidak valid.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $stmt = mysqli_prepare(
            $db,
            "UPDATE barang 
             SET status = 'sudah_sampai' 
             WHERE id_barang = ? AND id_kurir = ? AND status = 'sedang_dikirim'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $shipmentId, $courierId);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                set_flash('success', 'Paket PKF-' . str_pad((string)$shipmentId, 4, '0', STR_PAD_LEFT) . ' berhasil ditandai sebagai sudah sampai.');
            } else {
                set_flash('danger', 'Gagal memperbarui status pengiriman paket.');
            }
        }

        header('Location: courier-dashboard.php#deliveries');
        exit;
    }

    // 4. Update Profile
    if ($action === 'update_profile') {
        $newName = trim($_POST['name'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');
        $newAddress = trim($_POST['address'] ?? '');

        if ($newName === '' || $newPhone === '' || $newAddress === '') {
            set_flash('danger', 'Data profil tidak lengkap.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $stmt = mysqli_prepare(db: $db, query: 'UPDATE users SET nama = ?, telpon = ?, alamat = ? WHERE id = ?');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sssi', $newName, $newPhone, $newAddress, $user['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            set_flash('success', 'Profil kurir berhasil diperbarui.');
        }

        header('Location: courier-dashboard.php');
        exit;
    }

    // 5. Change Password
    if ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            set_flash('danger', 'Semua kolom password wajib diisi.');
            header('Location: courier-dashboard.php');
            exit;
        }

        if (strlen($newPassword) < 8) {
            set_flash('danger', 'Password baru minimal 8 karakter.');
            header('Location: courier-dashboard.php');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            set_flash('danger', 'Konfirmasi password baru tidak sama dengan password baru.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $stmt = mysqli_prepare($db, 'SELECT password_hash FROM users WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $user['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
            set_flash('danger', 'Password lama tidak sesuai.');
            header('Location: courier-dashboard.php');
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = mysqli_prepare($db, 'UPDATE users SET password_hash = ? WHERE id = ?');
        mysqli_stmt_bind_param($update, 'si', $newHash, $user['id']);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);

        set_flash('success', 'Password berhasil diubah.');
        header('Location: courier-dashboard.php');
        exit;
    }
}

/* =====================================================
   FETCH DATA FROM DATABASE
===================================================== */
// 1. Available Shipments (belum_dikirim & belum diambil siapapun)
$availableShipments = [];
$stmtAvailable = mysqli_prepare(
    $db,
    "SELECT b.*, u.nama AS nama_pengirim, u.alamat AS alamat_asal, u.telpon AS telpon_pengirim
     FROM barang b
     JOIN users u ON u.id = b.id_pengirim
     WHERE b.status = 'belum_dikirim' AND (b.id_kurir IS NULL OR b.id_kurir = 0)
     ORDER BY b.id_barang DESC"
);
if ($stmtAvailable) {
    mysqli_stmt_execute($stmtAvailable);
    $res = mysqli_stmt_get_result($stmtAvailable);
    while ($r = mysqli_fetch_assoc($res)) {
        $availableShipments[] = $r;
    }
    mysqli_stmt_close($stmtAvailable);
}

// 2. My Active Deliveries (sedang_dikirim oleh kurir yang sedang login)
$myActiveDeliveries = [];
$stmtMyActive = mysqli_prepare(
    $db,
    "SELECT b.*, u.nama AS nama_pengirim, u.alamat AS alamat_asal, u.telpon AS telpon_pengirim
     FROM barang b
     JOIN users u ON u.id = b.id_pengirim
     WHERE b.status = 'sedang_dikirim' AND b.id_kurir = ?
     ORDER BY b.id_barang DESC"
);
if ($stmtMyActive) {
    mysqli_stmt_bind_param($stmtMyActive, 'i', $courierId);
    mysqli_stmt_execute($stmtMyActive);
    $res = mysqli_stmt_get_result($stmtMyActive);
    while ($r = mysqli_fetch_assoc($res)) {
        $myActiveDeliveries[] = $r;
    }
    mysqli_stmt_close($stmtMyActive);
}

// 3. My Completed Deliveries (sudah_sampai oleh kurir ini)
$myCompletedDeliveries = [];
$stmtCompleted = mysqli_prepare(
    $db,
    "SELECT b.*, u.nama AS nama_pengirim, u.alamat AS alamat_asal, u.telpon AS telpon_pengirim
     FROM barang b
     JOIN users u ON u.id = b.id_pengirim
     WHERE b.status = 'sudah_sampai' AND b.id_kurir = ?
     ORDER BY b.updated_at DESC, b.id_barang DESC
     LIMIT 50"
);
if ($stmtCompleted) {
    mysqli_stmt_bind_param($stmtCompleted, 'i', $courierId);
    mysqli_stmt_execute($stmtCompleted);
    $res = mysqli_stmt_get_result($stmtCompleted);
    while ($r = mysqli_fetch_assoc($res)) {
        $myCompletedDeliveries[] = $r;
    }
    mysqli_stmt_close($stmtCompleted);
}

// Counts & Stats
$countAvailable = count($availableShipments);
$countActive = count($myActiveDeliveries);
$countCompleted = count($myCompletedDeliveries);
$totalScheduled = $countActive + $countCompleted;
$completionPct = $totalScheduled > 0 ? (int) round(($countCompleted / $totalScheduled) * 100) : 0;

// Next Stop: prioritize active delivery, else next available package
$nextShipment = !empty($myActiveDeliveries) ? $myActiveDeliveries[0] : (!empty($availableShipments) ? $availableShipments[0] : null);

// Primary active delivery focus
$focusDelivery = !empty($myActiveDeliveries) ? $myActiveDeliveries[0] : null;

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Packify courier dashboard">
    <title>Courier Dashboard — Packify</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* Exact horizontal alignment for action buttons in route rows */
        .courier-dashboard .courier-route-item {
            display: grid !important;
            grid-template-columns: 42px minmax(180px, 1.8fr) minmax(130px, 1.1fr) auto !important;
            align-items: center !important;
            gap: 16px !important;
            padding: 14px 12px !important;
            min-height: 72px !important;
        }

        .courier-dashboard .route-action {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 8px !important;
            margin: 0 !important;
            padding: 0 !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
        }

        .courier-dashboard .route-action form {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
            vertical-align: middle !important;
        }

        .courier-dashboard .route-action .route-button,
        .courier-dashboard .route-action button {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            margin: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding: 0 14px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            border-radius: 8px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            text-decoration: none !important;
        }

        .courier-dashboard .next-stop-card .next-stop-actions,
        .courier-dashboard .delivery-actions {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 8px !important;
            margin-top: 20px !important;
            flex-wrap: wrap !important;
        }

        .courier-dashboard .next-stop-card .next-stop-actions form,
        .courier-dashboard .delivery-actions form {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .courier-dashboard .next-stop-card .next-stop-actions .route-button,
        .courier-dashboard .delivery-actions .route-button {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 38px !important;
            min-height: 38px !important;
            max-height: 38px !important;
            margin: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding: 0 16px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            border-radius: 9px !important;
            cursor: pointer !important;
            line-height: 1 !important;
            box-sizing: border-box !important;
        }

        .courier-dashboard .route-time {
            text-align: right !important;
            white-space: nowrap !important;
        }

        @media (max-width: 1024px) {
            .courier-dashboard .courier-route-item {
                grid-template-columns: 38px 1fr auto !important;
                gap: 12px !important;
            }
            .courier-dashboard .route-time {
                display: none !important;
            }
        }

        @media (max-width: 640px) {
            .courier-dashboard .courier-route-item {
                grid-template-columns: 36px 1fr !important;
            }
            .courier-dashboard .route-action {
                grid-column: span 2 !important;
                justify-content: flex-start !important;
                margin-top: 8px !important;
            }
        }
    </style>
</head>

<body>

<div class="dashboard courier-dashboard">

    <!-- =================================================
         SIDEBAR
    ================================================== -->
    <aside class="sidebar">
        <a href="index.php" class="dashboard-brand">
            Pack<span>i</span>fy
        </a>

        <div class="sidebar-label">
            COURIER
        </div>

        <nav>
            <a class="active" href="#overview">
                <span>01</span>
                Overview
            </a>
            <a href="#tasks">
                <span>02</span>
                Today's tasks (<?= $countAvailable ?>)
            </a>
            <a href="#deliveries">
                <span>03</span>
                Deliveries (<?= $countActive ?>)
            </a>
            <a href="#history">
                <span>04</span>
                Performance
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="javascript:void(0)" onclick="openModal(document.getElementById('profileModal'))">
                Settings
            </a>
            <a href="logout.php">
                Log out
            </a>
        </div>
    </aside>

    <!-- =================================================
         MAIN
    ================================================== -->
    <main class="dashboard-main">

        <!-- =================================================
             HEADER
        ================================================== -->
        <header class="dashboard-header">
            <div class="dashboard-heading">
                <div class="small-label">
                    COURIER DASHBOARD
                </div>
                <h1 id="overview">
                    Good morning, <?= htmlspecialchars($firstName) ?>.
                </h1>
                <p>
                    Here's your delivery route for today.
                </p>
            </div>

            <!-- Profile Menu (Top Right) -->
            <div class="profile" onclick="openModal(document.getElementById('profileModal'))" title="Edit profil & password" style="cursor: pointer;">
                <div class="avatar">
                    <?= htmlspecialchars($initial) ?>
                </div>
                <div class="profile-info">
                    <strong>
                        <?= htmlspecialchars($name) ?>
                    </strong>
                    <span>
                        Courier
                    </span>
                </div>
            </div>
        </header>

        <!-- FLASH NOTIFICATION -->
        <?php if ($flash): ?>
            <div style="padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 0.95rem; display: flex; align-items: center; justify-content: space-between; background: <?= ($flash['type'] ?? '') === 'danger' ? '#fde8e8' : 'var(--green-soft)' ?>; color: <?= ($flash['type'] ?? '') === 'danger' ? '#9b1c1c' : 'var(--green-dark)' ?>; border: 1px solid <?= ($flash['type'] ?? '') === 'danger' ? '#f8b4b4' : 'rgba(109, 168, 60, 0.25)' ?>;">
                <span><?= htmlspecialchars($flash['message'] ?? '') ?></span>
                <button type="button" style="background:none;border:none;cursor:pointer;font-size:1.2rem;line-height:1;color:inherit;" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- =================================================
             TODAY OVERVIEW
        ================================================== -->
        <section class="courier-overview-grid">

            <!-- WORKLOAD -->
            <div class="courier-hero" data-reveal>
                <div class="courier-hero-top">
                    <div>
                        <span class="small-label">
                            TODAY'S WORKLOAD
                        </span>
                        <h2>
                            Keep moving.
                        </h2>
                    </div>
                    <span class="live-status">
                        <i></i>
                        ACTIVE
                    </span>
                </div>

                <div class="workload-number">
                    <strong><?= str_pad((string)($countActive + $countAvailable), 2, '0', STR_PAD_LEFT) ?></strong>
                    <span>
                        stops<br>
                        scheduled
                    </span>
                </div>

                <div class="workload-progress">
                    <div class="workload-progress-head">
                        <span>
                            Daily completion
                        </span>
                        <strong>
                            <?= $completionPct ?>%
                        </strong>
                    </div>
                    <div class="workload-track">
                        <div class="workload-fill" style="width: <?= $completionPct ?>%;"></div>
                    </div>
                </div>

                <div class="hero-meta">
                    <div>
                        <span>COMPLETED</span>
                        <strong><?= str_pad((string)$countCompleted, 2, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                    <div>
                        <span>IN TRANSIT</span>
                        <strong><?= str_pad((string)$countActive, 2, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                    <div>
                        <span>AVAILABLE</span>
                        <strong><?= str_pad((string)$countAvailable, 2, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                </div>
            </div>

            <!-- NEXT STOP -->
            <div class="next-stop-card" data-reveal>
                <div class="next-stop-label">
                    <span class="small-label">
                        <?= (!empty($myActiveDeliveries)) ? 'CURRENT DELIVERY' : 'NEXT STOP' ?>
                    </span>
                    <span class="next-stop-time">
                        <?= $nextShipment ? 'PKF-' . str_pad((string)$nextShipment['id_barang'], 4, '0', STR_PAD_LEFT) : '—' ?>
                    </span>
                </div>

                <div class="stop-icon">
                    →
                </div>

                <?php if ($nextShipment): ?>
                    <h3>
                        <?= htmlspecialchars('PKF-' . str_pad((string)$nextShipment['id_barang'], 4, '0', STR_PAD_LEFT)) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($nextShipment['alamat_tujuan']) ?>
                    </p>

                    <div class="stop-route">
                        <span>
                            <?= htmlspecialchars($nextShipment['alamat_asal']) ?>
                        </span>
                        <div class="mini-route">
                            <i></i>
                            <i></i>
                            <i></i>
                        </div>
                        <span>
                            <?= htmlspecialchars($nextShipment['alamat_tujuan']) ?>
                        </span>
                    </div>

                    <div class="next-stop-actions">
                        <?php if ($nextShipment['status'] === 'belum_dikirim'): ?>
                            <form method="post" action="courier-dashboard.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="take_shipment">
                                <input type="hidden" name="shipment_id" value="<?= (int) $nextShipment['id_barang'] ?>">
                                <button type="submit" class="route-button dark">
                                    Ambil Paket <span>→</span>
                                </button>
                            </form>
                        <?php elseif ($nextShipment['status'] === 'sedang_dikirim' && (int)$nextShipment['id_kurir'] === $courierId): ?>
                            <form method="post" action="courier-dashboard.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="complete_shipment">
                                <input type="hidden" name="shipment_id" value="<?= (int) $nextShipment['id_barang'] ?>">
                                <button type="submit" class="route-button">
                                    Tandai Selesai <span>✓</span>
                                </button>
                            </form>
                            <form method="post" action="courier-dashboard.php" onsubmit="return confirm('Batalkan pengambilan paket ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel_pickup">
                                <input type="hidden" name="shipment_id" value="<?= (int) $nextShipment['id_barang'] ?>">
                                <button type="submit" class="route-button" style="color: #c81e1e; border-color: #f8b4b4; background: #fde8e8;">
                                    Batal Ambil
                                </button>
                            </form>
                        <?php endif; ?>

                        <button type="button" class="route-button" onclick="openDetailModal(<?= htmlspecialchars(json_encode($nextShipment), ENT_QUOTES, 'UTF-8') ?>)">
                            View details <span>→</span>
                        </button>
                    </div>
                <?php else: ?>
                    <h3>
                        No pending shipment
                    </h3>
                    <p>
                        There are no shipments waiting for pickup.
                    </p>
                <?php endif; ?>
            </div>

        </section>

        <!-- =================================================
             QUICK STATS
        ================================================== -->
        <section class="stats-grid courier-stats">
            <div class="stat-card" data-reveal>
                <span>TODAY'S PICKUPS</span>
                <strong><?= str_pad((string)$countAvailable, 2, '0', STR_PAD_LEFT) ?></strong>
                <small>Scheduled for pickup</small>
                <div class="stat-indicator">
                    <i></i>
                    <span><?= $countAvailable ?> available</span>
                </div>
            </div>

            <div class="stat-card" data-reveal>
                <span>DELIVERIES</span>
                <strong><?= str_pad((string)$countActive, 2, '0', STR_PAD_LEFT) ?></strong>
                <small>Packages on route</small>
                <div class="stat-indicator">
                    <i></i>
                    <span>Active route</span>
                </div>
            </div>

            <div class="stat-card" data-reveal>
                <span>COMPLETED</span>
                <strong><?= str_pad((string)$countCompleted, 2, '0', STR_PAD_LEFT) ?></strong>
                <small>Successfully delivered</small>
                <div class="stat-indicator completed-indicator">
                    <i></i>
                    <span>On schedule</span>
                </div>
            </div>
        </section>

        <!-- =================================================
             TODAY'S ROUTE (AVAILABLE SHIPMENTS)
        ================================================== -->
        <section class="panel courier-route-panel" id="tasks" data-reveal>
            <div class="panel-heading">
                <div>
                    <span class="small-label">
                        TODAY
                    </span>
                    <h2>
                        Delivery route (Available)
                    </h2>
                </div>
                <span class="status">
                    <?= $countAvailable ?> available
                </span>
            </div>

            <div class="courier-route-list" id="courierShipmentList">
                <?php if (empty($availableShipments)): ?>
                    <div class="empty-state">
                        <span>○</span>
                        <strong>No available shipments</strong>
                        <p>Customer orders waiting for pickup will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($availableShipments as $index => $shipment): ?>
                        <div class="courier-route-item">
                            <div class="route-index">
                                <span>
                                    <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                            </div>

                            <div class="route-main">
                                <div class="route-title">
                                    <strong>
                                        <?= htmlspecialchars('PKF-' . str_pad((string)$shipment['id_barang'], 4, '0', STR_PAD_LEFT)) ?>
                                    </strong>
                                    <span class="status">
                                        Pickup
                                    </span>
                                </div>

                                <span class="route-location">
                                    <?= htmlspecialchars($shipment['alamat_asal']) ?>
                                    →
                                    <?= htmlspecialchars($shipment['alamat_tujuan']) ?>
                                </span>
                            </div>

                            <div class="route-time">
                                <strong>
                                    <?= htmlspecialchars($shipment['nama_penerima']) ?>
                                </strong>
                                <span>
                                    From: <?= htmlspecialchars($shipment['nama_pengirim']) ?>
                                </span>
                            </div>

                            <div class="route-action">
                                <button type="button" class="route-button" onclick="openDetailModal(<?= htmlspecialchars(json_encode($shipment), ENT_QUOTES, 'UTF-8') ?>)">
                                    View
                                </button>
                                <form method="post" action="courier-dashboard.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="take_shipment">
                                    <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id_barang'] ?>">
                                    <button type="submit" class="route-button dark">
                                        Ambil Paket
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- =================================================
             DELIVERY INFORMATION (PAKET SAYA / IN TRANSIT)
        ================================================== -->
        <section class="content-grid courier-info-grid" id="deliveries">

            <!-- ROUTE STATUS -->
            <div class="panel route-status-panel" data-reveal>
                <div class="panel-heading">
                    <div>
                        <span class="small-label">
                            ROUTE STATUS
                        </span>
                        <h2>
                            <?= $countActive > 0 ? 'Route in progress' : 'Ready for orders' ?>
                        </h2>
                    </div>
                    <div class="route-status-icon">
                        ✓
                    </div>
                </div>

                <p>
                    <?= $countActive > 0 ? "You currently have {$countActive} package(s) on your delivery route." : "No active deliveries right now. Claim a package from Today's tasks above to start." ?>
                </p>

                <div class="route-status-line">
                    <div class="status-pulse">
                        <i></i>
                    </div>
                    <div>
                        <strong>
                            <?= $countActive > 0 ? 'Active route (' . $countActive . ' items)' : 'Courier is idle' ?>
                        </strong>
                        <span>
                            Last updated just now
                        </span>
                    </div>
                </div>
            </div>

            <!-- NEXT DELIVERY / ACTIVE FOCUS -->
            <div class="panel delivery-focus" data-reveal>
                <span class="small-label">
                    MY DELIVERIES (PAKET SAYA)
                </span>

                <?php if ($focusDelivery): ?>
                    <div class="delivery-focus-header">
                        <h2>
                            <?= htmlspecialchars('PKF-' . str_pad((string)$focusDelivery['id_barang'], 4, '0', STR_PAD_LEFT)) ?>
                        </h2>
                        <span class="status in-transit">
                            In transit
                        </span>
                    </div>

                    <div class="delivery-address">
                        <span>
                            DELIVERY TO
                        </span>
                        <strong>
                            <?= htmlspecialchars($focusDelivery['nama_penerima']) ?> — <?= htmlspecialchars($focusDelivery['alamat_tujuan']) ?>
                        </strong>
                    </div>

                    <div class="delivery-actions">
                        <form method="post" action="courier-dashboard.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="complete_shipment">
                            <input type="hidden" name="shipment_id" value="<?= (int) $focusDelivery['id_barang'] ?>">
                            <button type="submit" class="route-button">
                                Tandai Selesai <span>✓</span>
                            </button>
                        </form>

                        <form method="post" action="courier-dashboard.php" onsubmit="return confirm('Batalkan pengambilan paket ini?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel_pickup">
                            <input type="hidden" name="shipment_id" value="<?= (int) $focusDelivery['id_barang'] ?>">
                            <button type="submit" class="route-button" style="color: #c81e1e; border-color: #f8b4b4; background: #fde8e8;">
                                Batal
                            </button>
                        </form>

                        <button type="button" class="route-button dark" onclick="openDetailModal(<?= htmlspecialchars(json_encode($focusDelivery), ENT_QUOTES, 'UTF-8') ?>)">
                            View details <span>→</span>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="delivery-focus-header">
                        <h2>
                            No active package
                        </h2>
                        <span class="status">
                            Empty
                        </span>
                    </div>

                    <div class="delivery-address">
                        <span>
                            STATUS
                        </span>
                        <strong>
                            You have not taken any package yet.
                        </strong>
                    </div>
                <?php endif; ?>
            </div>

        </section>

        <!-- ACTIVE SHIPMENTS LIST (IF MULTIPLE) -->
        <?php if ($countActive > 1): ?>
            <section class="panel courier-route-panel" data-reveal style="margin-bottom: 24px;">
                <div class="panel-heading">
                    <div>
                        <span class="small-label">PAKET SAYA</span>
                        <h2>All active deliveries (<?= $countActive ?>)</h2>
                    </div>
                </div>

                <div class="courier-route-list">
                    <?php foreach ($myActiveDeliveries as $index => $shipment): ?>
                        <div class="courier-route-item current">
                            <div class="route-index">
                                <span><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <div class="route-main">
                                <div class="route-title">
                                    <strong><?= htmlspecialchars('PKF-' . str_pad((string)$shipment['id_barang'], 4, '0', STR_PAD_LEFT)) ?></strong>
                                    <span class="status in-transit">In transit</span>
                                </div>
                                <span class="route-location">
                                    <?= htmlspecialchars($shipment['alamat_asal']) ?> → <?= htmlspecialchars($shipment['alamat_tujuan']) ?>
                                </span>
                            </div>
                            <div class="route-time">
                                <strong><?= htmlspecialchars($shipment['nama_penerima']) ?></strong>
                                <span>From: <?= htmlspecialchars($shipment['nama_pengirim']) ?></span>
                            </div>
                            <div class="route-action">
                                <button type="button" class="route-button" onclick="openDetailModal(<?= htmlspecialchars(json_encode($shipment), ENT_QUOTES, 'UTF-8') ?>)">View</button>
                                <form method="post" action="courier-dashboard.php" onsubmit="return confirm('Batalkan pengambilan paket ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel_pickup">
                                    <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id_barang'] ?>">
                                    <button type="submit" class="route-button" style="color:#c81e1e; border-color: #f8b4b4; background: #fde8e8;">Batal</button>
                                </form>
                                <form method="post" action="courier-dashboard.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete_shipment">
                                    <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id_barang'] ?>">
                                    <button type="submit" class="route-button">Tandai Selesai</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- =================================================
             PERFORMANCE
        ================================================== -->
        <section class="panel courier-performance" id="history" data-reveal>
            <div class="panel-heading">
                <div>
                    <span class="small-label">
                        PERFORMANCE
                    </span>
                    <h2>
                        Today's progress
                    </h2>
                </div>
                <span class="status delivered">
                    On track
                </span>
            </div>

            <div class="performance-grid">
                <div class="performance-item">
                    <div class="performance-head">
                        <span>Pickup completion</span>
                        <strong><?= $completionPct ?>%</strong>
                    </div>
                    <div class="performance-track">
                        <div class="performance-fill" style="width: <?= $completionPct ?>%;"></div>
                    </div>
                    <small><?= $countCompleted ?> of <?= $totalScheduled ?> deliveries</small>
                </div>

                <div class="performance-item">
                    <div class="performance-head">
                        <span>Active deliveries</span>
                        <strong><?= $countActive ?> active</strong>
                    </div>
                    <div class="performance-track">
                        <div class="performance-fill" style="width: <?= $totalScheduled > 0 ? round(($countActive / $totalScheduled) * 100) : 0 ?>%;"></div>
                    </div>
                    <small><?= $countActive ?> packages on the road</small>
                </div>

                <div class="performance-item">
                    <div class="performance-head">
                        <span>Completed delivered</span>
                        <strong><?= $countCompleted ?> packages</strong>
                    </div>
                    <div class="performance-track">
                        <div class="performance-fill" style="width: <?= $countCompleted > 0 ? 100 : 0 ?>%;"></div>
                    </div>
                    <small>Delivered successfully</small>
                </div>
            </div>
        </section>

        <!-- =================================================
             QUICK ACTION
        ================================================== -->
        <section class="quick-action" id="settings" data-reveal>
            <div>
                <span class="small-label">
                    COURIER STATUS
                </span>
                <h2>
                    You're ready for the next stop.
                </h2>
                <p>
                    Keep your delivery status updated so customers can follow their packages.
                </p>
            </div>

            <a href="logout.php" class="btn-primary">
                End session <span>→</span>
            </a>
        </section>

    </main>

</div>

<!-- =================================================
     SHIPMENT DETAIL MODAL
================================================== -->
<div class="packify-modal" id="shipmentDetailModal" aria-hidden="true">
    <div class="packify-modal-backdrop"></div>
    <div class="packify-modal-card">
        <div class="packify-modal-header">
            <div>
                <span class="small-label">SHIPMENT DETAIL</span>
                <h2 id="modalDetailShipmentId">PKF-0001</h2>
            </div>
            <button type="button" class="modal-close" data-close-modal>×</button>
        </div>

        <div class="detail-status" style="margin-bottom: 20px;">
            <span>CURRENT STATUS</span>
            <span class="status" id="modalDetailStatus">Belum dikirim</span>
        </div>

        <div class="detail-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="detail-item">
                <span>PENGIRIM</span>
                <strong id="modalDetailSender">-</strong>
            </div>
            <div class="detail-item">
                <span>ALAMAT ASAL</span>
                <strong id="modalDetailOrigin">-</strong>
            </div>
            <div class="detail-item">
                <span>PENERIMA</span>
                <strong id="modalDetailRecipient">-</strong>
            </div>
            <div class="detail-item">
                <span>ALAMAT TUJUAN</span>
                <strong id="modalDetailDestination">-</strong>
            </div>
            <div class="detail-item">
                <span>BERAT PAKET</span>
                <strong id="modalDetailWeight">-</strong>
            </div>
            <div class="detail-item">
                <span>JUMLAH BARANG</span>
                <strong id="modalDetailQuantity">-</strong>
            </div>
            <div class="detail-item" style="grid-column: span 2;">
                <span>WAKTU DIBUAT</span>
                <strong id="modalDetailCreated">-</strong>
            </div>
        </div>

        <div id="modalActionContainer" style="padding-top: 16px; border-top: 1px solid var(--line); display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
            <!-- Rendered dynamically by openDetailModal() -->
        </div>
    </div>
</div>

<!-- =================================================
     PROFILE & PASSWORD MODAL
================================================== -->
<div class="packify-modal" id="profileModal" aria-hidden="true">
    <div class="packify-modal-backdrop"></div>
    <div class="packify-modal-card">
        <div class="packify-modal-header">
            <div>
                <span class="small-label">ACCOUNT</span>
                <h2>Edit profile</h2>
            </div>
            <button type="button" class="modal-close" data-close-modal>×</button>
        </div>

        <!-- FORM UPDATE PROFILE -->
        <form class="packify-form" id="profileForm" method="POST" action="courier-dashboard.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-field">
                <label>FULL NAME</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-field">
                <label>PHONE</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telpon'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
            </div>

            <div class="form-field">
                <label>ADDRESS</label>
                <textarea name="address" placeholder="Your address" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="form-button secondary" data-close-modal>Cancel</button>
                <button type="submit" class="form-button primary">Save changes</button>
            </div>
        </form>

        <!-- FORM CHANGE PASSWORD -->
        <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid rgba(0,0,0,.08);">
            <div class="packify-modal-header" style="margin-bottom: 16px;">
                <div>
                    <span class="small-label">SECURITY</span>
                    <h2>Change password</h2>
                </div>
            </div>

            <form class="packify-form" id="passwordForm" method="POST" action="courier-dashboard.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-field">
                    <label>CURRENT PASSWORD</label>
                    <input type="password" name="old_password" required autocomplete="current-password">
                </div>

                <div class="form-field">
                    <label>NEW PASSWORD</label>
                    <input type="password" name="new_password" minlength="8" required autocomplete="new-password">
                </div>

                <div class="form-field">
                    <label>CONFIRM NEW PASSWORD</label>
                    <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </div>

                <div class="form-actions">
                    <button type="submit" class="form-button primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =================================================
     SCRIPTS & INTERACTIONS
================================================== -->
<script>
// Modal open/close functions
function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

// Close buttons & backdrop handlers
document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = this.closest('.packify-modal, .shipment-modal');
        closeModal(modal);
    });
});

document.querySelectorAll('.packify-modal-backdrop, .shipment-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function() {
        const modal = this.closest('.packify-modal, .shipment-modal');
        closeModal(modal);
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.packify-modal[aria-hidden="false"], .shipment-modal[aria-hidden="false"]').forEach(modal => {
            closeModal(modal);
        });
    }
});

// Detail Modal Handler
const currentCourierId = <?= $courierId ?>;
const csrfTokenInput = '<?= csrf_field() ?>';

function openDetailModal(shipment) {
    if (!shipment) return;

    const modal = document.getElementById('shipmentDetailModal');
    const formattedId = 'PKF-' + String(shipment.id_barang).padStart(4, '0');

    document.getElementById('modalDetailShipmentId').textContent = formattedId;
    
    // Status formatting
    const statusBadge = document.getElementById('modalDetailStatus');
    const rawStatus = (shipment.status || 'belum_dikirim').toLowerCase();
    
    if (rawStatus === 'sudah_sampai') {
        statusBadge.textContent = 'Sudah sampai';
        statusBadge.className = 'status delivered';
    } else if (rawStatus === 'sedang_dikirim') {
        statusBadge.textContent = 'Sedang dikirim';
        statusBadge.className = 'status in-transit';
    } else {
        statusBadge.textContent = 'Belum dikirim';
        statusBadge.className = 'status';
    }

    document.getElementById('modalDetailSender').textContent = shipment.nama_pengirim || '-';
    document.getElementById('modalDetailOrigin').textContent = shipment.alamat_asal || '-';
    document.getElementById('modalDetailRecipient').textContent = shipment.nama_penerima || '-';
    document.getElementById('modalDetailDestination').textContent = shipment.alamat_tujuan || '-';
    document.getElementById('modalDetailWeight').textContent = (shipment.berat_barang_kg || '1') + ' kg';
    document.getElementById('modalDetailQuantity').textContent = (shipment.jumlah_barang || '1') + ' item';
    document.getElementById('modalDetailCreated').textContent = shipment.created_at || '-';

    // Action buttons inside modal
    const actionContainer = document.getElementById('modalActionContainer');
    actionContainer.innerHTML = '';

    if (rawStatus === 'belum_dikirim') {
        actionContainer.innerHTML = `
            <button type="button" class="form-button secondary" data-close-modal onclick="closeModal(document.getElementById('shipmentDetailModal'))">Tutup</button>
            <form method="post" action="courier-dashboard.php" style="margin:0;">
                ${csrfTokenInput}
                <input type="hidden" name="action" value="take_shipment">
                <input type="hidden" name="shipment_id" value="${shipment.id_barang}">
                <button type="submit" class="route-button dark" style="height: 38px; padding: 0 16px; font-size: 11px;">Ambil Paket <span>→</span></button>
            </form>
        `;
    } else if (rawStatus === 'sedang_dikirim' && parseInt(shipment.id_kurir, 10) === currentCourierId) {
        actionContainer.innerHTML = `
            <form method="post" action="courier-dashboard.php" style="margin:0;" onsubmit="return confirm('Batalkan pengambilan paket ini?');">
                ${csrfTokenInput}
                <input type="hidden" name="action" value="cancel_pickup">
                <input type="hidden" name="shipment_id" value="${shipment.id_barang}">
                <button type="submit" class="route-button" style="height: 38px; padding: 0 16px; font-size: 11px; color: #c81e1e; border-color: #f8b4b4;">Batal Ambil</button>
            </form>
            <form method="post" action="courier-dashboard.php" style="margin:0;">
                ${csrfTokenInput}
                <input type="hidden" name="action" value="complete_shipment">
                <input type="hidden" name="shipment_id" value="${shipment.id_barang}">
                <button type="submit" class="route-button" style="height: 38px; padding: 0 16px; font-size: 11px;">Tandai Selesai <span>✓</span></button>
            </form>
        `;
    } else {
        actionContainer.innerHTML = `
            <button type="button" class="form-button secondary" onclick="closeModal(document.getElementById('shipmentDetailModal'))">Tutup</button>
        `;
    }

    openModal(modal);
}
</script>

</body>
</html>