<?php
require_once __DIR__ . '/functions.php';

start_session_safe();

$user = current_user();

if ($user === null || ($user['role'] ?? '') !== 'pelanggan') {
    header('Location: login.php?role=customer');
    exit;
}

$name = $user['nama'] ?? 'Customer';
$firstName = explode(' ', trim($name))[0];
$initial = strtoupper(substr(trim($name), 0, 1));

// Semua POST di dashboard ini wajib membawa CSRF token yang valid (fix: forms sebelumnya tidak dilindungi CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

// Handle change password (fix: sebelumnya modal ini hanya tampilan, tidak benar-benar mengubah password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        set_flash('danger', 'Semua kolom password wajib diisi.');
        header('Location: customer-dashboard.php');
        exit;
    }

    if (strlen($newPassword) < 8) {
        set_flash('danger', 'Password baru minimal 8 karakter.');
        header('Location: customer-dashboard.php');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        set_flash('danger', 'Konfirmasi password baru tidak sama dengan password baru.');
        header('Location: customer-dashboard.php');
        exit;
    }

    $stmt = mysqli_prepare($db, 'SELECT password_hash FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Validasi password lama benar-benar dicek (bukan sekadar dicek "kosong atau tidak")
    if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
        set_flash('danger', 'Password lama tidak sesuai.');
        header('Location: customer-dashboard.php');
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $update = mysqli_prepare($db, 'UPDATE users SET password_hash = ? WHERE id = ?');
    mysqli_stmt_bind_param($update, 'si', $newHash, $user['id']);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);

    set_flash('success', 'Password berhasil diubah.');
    header('Location: customer-dashboard.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $newName = trim($_POST['name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');

    if ($newName === '' || $newPhone === '' || $newAddress === '') {
        die('Data profile tidak lengkap.');
    }

    $stmt = mysqli_prepare($db, 'UPDATE users SET nama = ?, telpon = ?, alamat = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sssi', $newName, $newPhone, $newAddress, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: customer-dashboard.php?profile=updated');
    exit;
}

// Handle create shipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_shipment') {
    $namaPenerima = trim($_POST['nama_penerima'] ?? '');
    $beratBarang = (int) ($_POST['berat_barang'] ?? 0);
    $jumlahBarang = (int) ($_POST['jumlah_barang'] ?? 1);
    $alamatTujuan = trim($_POST['alamat_tujuan'] ?? '');
    $status = 'belum_dikirim';

    if ($namaPenerima === '' || $alamatTujuan === '' || $beratBarang < 1 || $jumlahBarang < 1) {
        die('Data barang tidak lengkap.');
    }

    $stmt = mysqli_prepare($db, 'INSERT INTO barang (id_pengirim, nama_penerima, berat_barang_kg, jumlah_barang, alamat_tujuan, status) VALUES (?, ?, ?, ?, ?, ?)');
    
    if (!$stmt) {
        die('Prepare statement gagal: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, 'isiiss', $user['id'], $namaPenerima, $beratBarang, $jumlahBarang, $alamatTujuan, $status);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die('Gagal membuat shipment: ' . htmlspecialchars($error));
    }

    mysqli_stmt_close($stmt);
    header('Location: customer-dashboard.php?shipment=created');
    exit;
}

// Handle update shipment (only for pending shipments)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_shipment') {
    $idBarang = (int) ($_POST['id_barang'] ?? 0);
    $namaPenerima = trim($_POST['nama_penerima'] ?? '');
    $alamatTujuan = trim($_POST['alamat_tujuan'] ?? '');
    $beratBarang = (int) ($_POST['berat_barang'] ?? 0);
    $jumlahBarang = (int) ($_POST['jumlah_barang'] ?? 1);

    if ($idBarang < 1 || $namaPenerima === '' || $alamatTujuan === '' || $beratBarang < 1 || $jumlahBarang < 1) {
        die('Data barang tidak lengkap.');
    }

    /* ====================== FIX: IDOR (Insecure Direct Object Reference) ======================
     * Query di bawah ini WAJIB menambahkan kondisi "AND id_pengirim = ?", supaya
     * pelanggan A yang sedang login tidak bisa mengedit paket milik pelanggan lain
     * dengan mengganti nilai id_barang di form/request.
     * =============================================================================== */
    $stmt = mysqli_prepare($db, 'UPDATE barang SET nama_penerima = ?, alamat_tujuan = ?, berat_barang_kg = ?, jumlah_barang = ? WHERE id_barang = ? AND id_pengirim = ? AND status = "belum_dikirim"');
    
    if (!$stmt) {
        die('Prepare statement gagal: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, 'ssiiii', $namaPenerima, $alamatTujuan, $beratBarang, $jumlahBarang, $idBarang, $user['id']);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die('Gagal mengedit shipment: ' . htmlspecialchars($error));
    }

    mysqli_stmt_close($stmt);
    header('Location: customer-dashboard.php?shipment=updated');
    exit;
}

// Handle cancel shipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_shipment') {
    $idBarang = (int) ($_POST['id_barang'] ?? 0);

    if ($idBarang < 1) {
        die('ID barang tidak valid.');
    }

    /* ====================== FIX: IDOR (Insecure Direct Object Reference) ======================
     * Query DELETE ini WAJIB memvalidasi id_pengirim terhadap user yang sedang login.
     * =============================================================================== */
    $stmt = mysqli_prepare($db, 'DELETE FROM barang WHERE id_barang = ? AND id_pengirim = ? AND status = "belum_dikirim"');
    mysqli_stmt_bind_param($stmt, 'ii', $idBarang, $user['id']);
    mysqli_stmt_execute($stmt);

    $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    header('Location: customer-dashboard.php?shipment=' . ($berhasil ? 'cancelled' : 'cancel_failed'));
    exit;
}

// Fetch customer shipments
$customerShipments = [];
$stmt = mysqli_prepare($db, 'SELECT id_barang, nama_penerima, berat_barang_kg, jumlah_barang, alamat_tujuan, status, created_at FROM barang WHERE id_pengirim = ? ORDER BY id_barang DESC');

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $customerShipments[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Calculate stats
$totalShipments = count($customerShipments);
$pendingShipments = array_filter($customerShipments, function($s) { return $s['status'] === 'belum_dikirim'; });
$inTransitShipments = array_filter($customerShipments, function($s) { return $s['status'] === 'sedang_dikirim'; });
$deliveredShipments = array_filter($customerShipments, function($s) { return $s['status'] === 'sudah_sampai'; });

// Get recent shipment (first one)
$recentShipment = !empty($customerShipments) ? $customerShipments[0] : null;

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Packify customer dashboard">
    <title>Dashboard — Packify</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="index.php" class="dashboard-brand">Pack<span>i</span>fy</a>
        <div class="sidebar-label">CUSTOMER</div>
        <nav>
            <a class="active" href="#overview"><span>01</span> Overview</a>
            <a href="#shipments"><span>02</span> My shipments</a>
            <a href="#tracking"><span>03</span> Track package</a>
            <a href="#history"><span>04</span> History</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="#settings">Settings</a>
            <a href="logout.php">Log out</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="dashboard-main">

        <!-- HEADER -->
        <header class="dashboard-header">
            <div class="dashboard-heading">
                <div class="small-label">CUSTOMER DASHBOARD</div>
                <h1 id="overview">Good morning, <?= htmlspecialchars($firstName) ?>.</h1>
                <p>Here's what's happening with your shipments.</p>
            </div>
            <div class="profile">
                <div class="avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="profile-info">
                    <strong><?= htmlspecialchars($name) ?></strong>
                    <span>Customer</span>
                </div>
            </div>
        </header>

        <!-- QUICK STATS -->
        <section class="stats-grid">
            <div class="stat-card" data-reveal>
                <span>ACTIVE SHIPMENTS</span>
                <strong><?= count($pendingShipments) ?></strong>
                <small>Waiting to be picked up</small>
            </div>
            <div class="stat-card" data-reveal>
                <span>DELIVERED</span>
                <strong><?= count($deliveredShipments) ?></strong>
                <small>Successfully delivered</small>
            </div>
            <div class="stat-card" data-reveal>
                <span>IN TRANSIT</span>
                <strong><?= count($inTransitShipments) ?></strong>
                <small>Currently on the way</small>
            </div>
        </section>

        <!-- SHIPMENT OVERVIEW + TRACKING -->
        <section class="content-grid">

            <!-- SHIPMENT LIST -->
            <section class="panel" data-reveal>
                <div class="panel-heading">
                    <div>
                        <span class="small-label">MY SHIPMENTS</span>
                        <h2>Your packages</h2>
                    </div>
                    <button type="button" class="form-button primary" id="newShipmentButton" onclick="openModal(document.getElementById('shipmentFormModal'))">
                        + New shipment
                    </button>
                </div>

                <div class="shipment-list" id="customerShipmentList">
                    <?php if (empty($customerShipments)): ?>
                        <div class="empty-state">
                            <span>○</span>
                            <strong>No shipments yet</strong>
                            <p>Create your first shipment to get started.</p>
                        </div>
                    <?php else: ?>
                        <?php $displayNumber = 1; ?>
                        <?php foreach ($customerShipments as $shipment): ?>
                            <div class="shipment-row">
                                <div class="shipment-main">
                                    <strong>#<?= $displayNumber ?></strong>
                                    <span><?= htmlspecialchars($shipment['nama_penerima']) ?></span>
                                </div>
                                <div class="shipment-recipient">
                                    <strong><?= $shipment['berat_barang_kg'] ?> kg</strong>
                                    <span><?= htmlspecialchars($shipment['alamat_tujuan']) ?></span>
                                </div>
                                <span class="status <?= $shipment['status'] === 'sudah_sampai' ? 'delivered' : ($shipment['status'] === 'sedang_dikirim' ? 'in-transit' : '') ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $shipment['status']))) ?>
                                </span>
                                <div class="shipment-actions">
                                    <button type="button" class="table-action" data-view-shipment="<?= (int) $shipment['id_barang'] ?>"
                                            data-nama-penerima="<?= htmlspecialchars($shipment['nama_penerima'], ENT_QUOTES) ?>"
                                            data-alamat-tujuan="<?= htmlspecialchars($shipment['alamat_tujuan'], ENT_QUOTES) ?>"
                                            data-berat-barang="<?= (int) $shipment['berat_barang_kg'] ?>"
                                            data-jumlah-barang="<?= (int) $shipment['jumlah_barang'] ?>"
                                            data-status="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $shipment['status'])), ENT_QUOTES) ?>"
                                            data-created-at="<?= htmlspecialchars(date('d M Y', strtotime($shipment['created_at'])), ENT_QUOTES) ?>">View</button>
                                    <?php if ($shipment['status'] === 'belum_dikirim'): ?>
                                        <button type="button" class="table-action" data-edit-shipment="<?= (int) $shipment['id_barang'] ?>" 
                                                data-nama-penerima="<?= htmlspecialchars($shipment['nama_penerima'], ENT_QUOTES) ?>"
                                                data-alamat-tujuan="<?= htmlspecialchars($shipment['alamat_tujuan'], ENT_QUOTES) ?>"
                                                data-berat-barang="<?= (int) $shipment['berat_barang_kg'] ?>"
                                                data-jumlah-barang="<?= (int) $shipment['jumlah_barang'] ?>">
                                            Edit
                                        </button>
                                        <button type="button" class="table-action" data-cancel-shipment="<?= (int) $shipment['id_barang'] ?>">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $displayNumber++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- RECENT SHIPMENT -->
            <div class="panel large-panel" id="shipments" data-reveal>
                <div class="panel-heading">
                    <div>
                        <span class="small-label">RECENT SHIPMENT</span>
                        <h2><?php
                        if ($recentShipment) {
                            $recentDisplayNumber = 1;
                            foreach ($customerShipments as $index => $s) {
                                if ((int) $s['id_barang'] === (int) $recentShipment['id_barang']) {
                                    $recentDisplayNumber = $index + 1;
                                    break;
                                }
                            }
                            echo '#' . $recentDisplayNumber;
                        } else {
                            echo 'No shipments';
                        }
                    ?></h2>
                    </div>
                    <span class="status <?= $recentShipment && $recentShipment['status'] === 'sudah_sampai' ? 'delivered' : '' ?>">
                        <?= $recentShipment ? htmlspecialchars(ucfirst(str_replace('_', ' ', $recentShipment['status']))) : 'N/A' ?>
                    </span>
                </div>

                <!-- ROUTE -->
                <div class="shipment-route">
                    <div class="route-location" style="text-align: center;">
                        <span>FROM</span>
                        <strong><?= htmlspecialchars($user['nama'] ?? 'Your location') ?></strong>
                    </div>
                    <div class="route-line">
                        <div class="route-progress"></div>
                        <div class="route-truck">→</div>
                    </div>
                    <div class="route-location destination" style="text-align: center;">
                        <span>TO</span>
                        <strong><?= $recentShipment ? htmlspecialchars($recentShipment['nama_penerima']) : 'N/A' ?></strong>
                    </div>
                </div>

                <!-- PROGRESS -->
                <div class="shipment-progress">
                    <div class="progress-heading">
                        <span>Shipment progress</span>
                        <strong><?= $recentShipment ? ($recentShipment['status'] === 'sudah_sampai' ? '100%' : ($recentShipment['status'] === 'sedang_dikirim' ? '50%' : '0%')) : '0%' ?></strong>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= $recentShipment ? ($recentShipment['status'] === 'sudah_sampai' ? '100' : ($recentShipment['status'] === 'sedang_dikirim' ? '50' : '0')) : '0' ?>%"></div>
                    </div>
                    <div class="progress-meta">
                        <span><?= $recentShipment ? ucfirst(str_replace('_', ' ', $recentShipment['status'])) : 'No status' ?></span>
                        <span>Created: <?= $recentShipment ? date('d M Y', strtotime($recentShipment['created_at'])) : 'N/A' ?></span>
                    </div>
                </div>

                <!-- TIMELINE -->
                <div class="tracking-line">
                    <div class="tracking-item <?= $recentShipment && $recentShipment['status'] !== 'belum_dikirim' ? 'completed' : 'current' ?>">
                        <div class="dot"></div>
                        <div>
                            <strong>Order created</strong>
                            <span><?= $recentShipment ? date('d M Y · H:i', strtotime($recentShipment['created_at'])) : 'N/A' ?></span>
                        </div>
                    </div>
                    <div class="tracking-item <?= $recentShipment && ($recentShipment['status'] === 'sedang_dikirim' || $recentShipment['status'] === 'sudah_sampai') ? 'completed' : '' ?>">
                        <div class="dot"></div>
                        <div>
                            <strong>Picked up</strong>
                            <span><?= $recentShipment && $recentShipment['status'] !== 'belum_dikirim' ? 'Package picked up by courier' : 'Awaiting pickup' ?></span>
                        </div>
                    </div>
                    <div class="tracking-item <?= $recentShipment && $recentShipment['status'] === 'sudah_sampai' ? 'completed' : '' ?>">
                        <div class="dot"></div>
                        <div>
                            <strong>Delivered</strong>
                            <span><?= $recentShipment && $recentShipment['status'] === 'sudah_sampai' ? 'Package delivered successfully' : 'Awaiting delivery' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRACK PACKAGE -->
            <div class="panel tracking-panel" id="tracking" data-reveal>
                <span class="small-label">TRACK PACKAGE</span>
                <h2>Where is your package?</h2>
                <p>Enter your shipment ID to see the latest status.</p>

                <form id="trackingForm" class="tracking-form">
                    <input type="text" name="tracking_id" placeholder="Shipment ID (e.g. 1)" autocomplete="off" maxlength="30" required>
                    <button type="submit">Track <span>→</span></button>
                </form>

                <div id="trackingResult" class="tracking-result"></div>

                <div class="tracking-tip">
                    <span class="tip-icon">i</span>
                    <div>
                        <strong>Tracking tip</strong>
                        <p>Enter your shipment ID number to check the current status.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- HISTORY -->
        <section class="panel activity" id="history" data-reveal>
            <div class="panel-heading">
                <div>
                    <span class="small-label">ACTIVITY</span>
                    <h2>Shipment history</h2>
                </div>
                <a href="#">View all →</a>
            </div>

            <?php 
            $historyShipments = array_slice($customerShipments, 0, 3);
            if (empty($historyShipments)): ?>
                <div class="empty-state" style="padding: 30px 0;">
                    <span>○</span>
                    <p>No shipment history yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($historyShipments as $historyIndex => $shipment): ?>
                    <div class="history-row">
                        <div class="history-info">
                            <strong>#<?= $historyIndex + 1 ?></strong>
                            <span><?= htmlspecialchars($shipment['nama_penerima']) ?> · <?= $shipment['berat_barang_kg'] ?> kg</span>
                        </div>
                        <span class="status <?= $shipment['status'] === 'sudah_sampai' ? 'delivered' : '' ?>">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $shipment['status']))) ?>
                        </span>
                        <span class="history-date"><?= date('d M Y', strtotime($shipment['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- QUICK ACTION -->
        <section class="quick-action" id="settings" data-reveal>
            <div>
                <span class="small-label">NEED SOMETHING ELSE?</span>
                <h2>Ready to send a package?</h2>
                <p>Start a new shipment and let Packify handle the journey.</p>
            </div>
            <a href="portal.php" class="btn-primary">Create shipment <span>→</span></a>
        </section>

    </main>
</div>

<!-- PROFILE MODAL -->
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
        <form class="packify-form" id="profileForm" method="POST" action="customer-dashboard.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-field">
                <label>FULL NAME</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="form-field">
                <label>PHONE</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telpon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-field">
                <label>ADDRESS</label>
                <textarea name="address" placeholder="Your address"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="form-button secondary" data-close-modal>Cancel</button>
                <button type="submit" class="form-button primary">Save changes</button>
            </div>
        </form>

        <!-- CHANGE PASSWORD -->
        <div class="profile-password-section" style="margin-top: 28px; padding-top: 24px; border-top: 1px solid rgba(0,0,0,.08);">
            <div class="packify-modal-header" style="margin-bottom: 18px;">
                <div>
                    <span class="small-label">SECURITY</span>
                    <h2>Change password</h2>
                </div>
            </div>

            <form class="packify-form" id="changePasswordForm" method="POST" action="edit.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-field">
                    <label>CURRENT PASSWORD</label>
                    <input type="password" name="password_lama" autocomplete="current-password" required>
                </div>

                <div class="form-field">
                    <label>NEW PASSWORD</label>
                    <input type="password" name="password_baru" minlength="8" autocomplete="new-password" required>
                </div>

                <div class="form-field">
                    <label>CONFIRM NEW PASSWORD</label>
                    <input type="password" name="konfirmasi_password_baru" minlength="8" autocomplete="new-password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="form-button primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PASSWORD MODAL -->
<div class="packify-modal" id="passwordModal" aria-hidden="true">
    <div class="packify-modal-backdrop"></div>
    <div class="packify-modal-card">
        <div class="packify-modal-header">
            <div>
                <span class="small-label">SECURITY</span>
                <h2>Change password</h2>
            </div>
            <button type="button" class="modal-close" data-close-modal>×</button>
        </div>
        <form class="packify-form" id="passwordForm">
            <div class="form-field">
                <label>CURRENT PASSWORD</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-field">
                <label>NEW PASSWORD</label>
                <input type="password" name="new_password" minlength="6" required>
            </div>
            <div class="form-field">
                <label>CONFIRM NEW PASSWORD</label>
                <input type="password" name="confirm_password" minlength="6" required>
            </div>
            <div class="form-actions">
                <button type="button" class="form-button secondary" data-close-modal>Cancel</button>
                <button type="submit" class="form-button primary">Update password</button>
            </div>
        </form>
    </div>
</div>

<!-- SHIPMENT FORM MODAL -->
<div class="packify-modal" id="shipmentFormModal" aria-hidden="true">
    <div class="packify-modal-backdrop"></div>
    <div class="packify-modal-card">
        <div class="packify-modal-header">
            <div>
                <span class="small-label">SHIPMENT</span>
                <h2>Create shipment</h2>
            </div>
            <button type="button" class="modal-close" data-close-modal>×</button>
        </div>
        <form class="packify-form" id="shipmentForm" method="POST" action="customer-dashboard.php">
            <input type="hidden" name="action" value="create_shipment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-field">
                <label>RECIPIENT NAME</label>
                <input type="text" name="nama_penerima" placeholder="Recipient full name" required>
            </div>
            <div class="form-field">
                <label>DELIVERY ADDRESS</label>
                <textarea name="alamat_tujuan" placeholder="Complete delivery address" required></textarea>
            </div>
            <div class="form-field">
                <label>WEIGHT (KG)</label>
                <input type="number" name="berat_barang" min="1" value="1" required>
            </div>
            <div class="form-field">
                <label>QUANTITY</label>
                <input type="number" name="jumlah_barang" min="1" value="1" required>
            </div>
            <div class="form-actions">
                <button type="button" class="form-button secondary" data-close-modal>Cancel</button>
                <button type="submit" class="form-button primary">Save shipment</button>
            </div>
        </form>
    </div>
</div>

<!-- SHIPMENT DETAIL MODAL -->
<div class="packify-modal" id="shipmentDetailModal" aria-hidden="true">
    <div class="packify-modal-backdrop"></div>
    <div class="packify-modal-card">
        <div class="packify-modal-header">
            <div>
                <span class="small-label">SHIPMENT DETAIL</span>
                <h2 id="detailShipmentId">#1</h2>
            </div>
            <button type="button" class="modal-close" data-close-modal>×</button>
        </div>
        <div class="detail-status">
            <span>CURRENT STATUS</span>
            <span class="status" id="detailStatus">Belum dikirim</span>
        </div>
        <div class="detail-grid">
            <div class="detail-item">
                <span>RECIPIENT</span>
                <strong id="detailRecipient">-</strong>
            </div>
            <div class="detail-item">
                <span>ADDRESS</span>
                <strong id="detailAddress">-</strong>
            </div>
            <div class="detail-item">
                <span>WEIGHT</span>
                <strong id="detailWeight">-</strong>
            </div>
            <div class="detail-item">
                <span>QUANTITY</span>
                <strong id="detailQuantity">-</strong>
            </div>
            <div class="detail-item">
                <span>CREATED</span>
                <strong id="detailCreated">-</strong>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>

<script>
// Enhanced JavaScript for handling the new structure
document.addEventListener('DOMContentLoaded', function() {
    const newShipmentButton = document.getElementById('newShipmentButton');
    if (newShipmentButton) {
        newShipmentButton.addEventListener('click', function() {
            const modal = document.getElementById('shipmentFormModal');
            const form = modal.querySelector('form');
            const title = modal.querySelector('h2');
            const submitBtn = form.querySelector('button[type="submit"]');

            title.textContent = 'Create shipment';
            submitBtn.textContent = 'Save shipment';
            form.querySelector('input[name="action"]').value = 'create_shipment';

            const idField = form.querySelector('input[name="id_barang"]');
            if (idField) idField.remove();

            form.reset();
        });
    }

    // View shipment details
    document.querySelectorAll('[data-view-shipment]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.viewShipment;

            const rows = Array.from(document.querySelectorAll('[data-view-shipment]'));
            const displayNumber = rows.indexOf(this) + 1;
            document.getElementById('detailShipmentId').textContent = '#' + displayNumber;
            document.getElementById('detailStatus').textContent = this.dataset.status || 'Belum dikirim';
            document.getElementById('detailRecipient').textContent = this.dataset.namaPenerima || '-';
            document.getElementById('detailAddress').textContent = this.dataset.alamatTujuan || '-';
            document.getElementById('detailWeight').textContent = (this.dataset.beratBarang || '-') + ' kg';
            document.getElementById('detailQuantity').textContent = this.dataset.jumlahBarang || '1';
            document.getElementById('detailCreated').textContent = this.dataset.createdAt || '-';

            openModal(document.getElementById('shipmentDetailModal'));
        });
    });

    // Edit shipment
    document.querySelectorAll('[data-edit-shipment]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.editShipment;
            const namaPenerima = this.dataset.namaPenerima || '';
            const alamatTujuan = this.dataset.alamatTujuan || '';
            const beratBarang = this.dataset.beratBarang || '1';
            const jumlahBarang = this.dataset.jumlahBarang || '1';
            
            const modal = document.getElementById('shipmentFormModal');
            const form = modal.querySelector('form');
            const title = modal.querySelector('h2');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            title.textContent = 'Edit shipment';
            submitBtn.textContent = 'Update shipment';
            form.querySelector('input[name="action"]').value = 'update_shipment';
            
            // Add hidden id field if not exists
            let idField = form.querySelector('input[name="id_barang"]');
            if (!idField) {
                idField = document.createElement('input');
                idField.type = 'hidden';
                idField.name = 'id_barang';
                form.appendChild(idField);
            }
            idField.value = id;
            
            form.querySelector('input[name="nama_penerima"]').value = namaPenerima;
            form.querySelector('textarea[name="alamat_tujuan"]').value = alamatTujuan;
            form.querySelector('input[name="berat_barang"]').value = beratBarang;
            form.querySelector('input[name="jumlah_barang"]').value = jumlahBarang;
            
            openModal(modal);
        });
    });

    // Cancel shipment
    document.querySelectorAll('[data-cancel-shipment]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.cancelShipment;

            if (!id || isNaN(parseInt(id, 10))) {
                alert('ID barang tidak terdeteksi. Muat ulang halaman lalu coba lagi.');
                return;
            }

            if (confirm('Are you sure you want to cancel this shipment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'customer-dashboard.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_shipment">
                    <input type="hidden" name="id_barang" value="${id}">
                    <input type="hidden" name="csrf_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Reset modal on close
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.packify-modal');
            closeModal(modal);
            
            // Reset shipment form if it's the shipment modal
            if (modal.id === 'shipmentFormModal') {
                const form = modal.querySelector('form');
                const title = modal.querySelector('h2');
                const submitBtn = form.querySelector('button[type="submit"]');
                title.textContent = 'Create shipment';
                submitBtn.textContent = 'Save shipment';
                form.querySelector('input[name="action"]').value = 'create_shipment';
                const idField = form.querySelector('input[name="id_barang"]');
                if (idField) idField.remove();
                form.reset();
            }
        });
    });

    // Tracking form handler
    document.getElementById('trackingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const trackingId = this.querySelector('input[name="tracking_id"]').value.trim();
        const resultDiv = document.getElementById('trackingResult');
        
        if (!trackingId) {
            resultDiv.innerHTML = '<p style="color: #e74c3c;">Please enter a shipment ID.</p>';
            return;
        }
        
        // Find shipment in the list
        const rows = document.querySelectorAll('.shipment-row');
        let found = false;
        
        rows.forEach(row => {
            const viewBtn = row.querySelector('[data-view-shipment]');
            if (viewBtn && viewBtn.dataset.viewShipment === trackingId) {
                found = true;
                const recipient = row.querySelector('.shipment-main span')?.textContent || '-';
                const address = row.querySelector('.shipment-recipient span')?.textContent || '-';
                const status = row.querySelector('.status')?.textContent || 'Belum dikirim';
                const weight = row.querySelector('.shipment-recipient strong')?.textContent || '-';
                
                resultDiv.innerHTML = `
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin-top: 15px;">
                        <strong style="display: block; margin-bottom: 8px;">Shipment #${trackingId}</strong>
                        <p><strong>Recipient:</strong> ${recipient}</p>
                        <p><strong>Address:</strong> ${address}</p>
                        <p><strong>Weight:</strong> ${weight}</p>
                        <p><strong>Status:</strong> <span class="status ${status.toLowerCase().includes('sudah_sampai') ? 'delivered' : ''}">${status}</span></p>
                    </div>
                `;
                resultDiv.style.display = 'block';
            }
        });
        
        if (!found) {
            resultDiv.innerHTML = `<p style="color: #e74c3c;">Shipment #${trackingId} not found.</p>`;
            resultDiv.style.display = 'block';
        }
    });
});

// Modal functions
function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

// Close modal on backdrop click
document.querySelectorAll('.packify-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function() {
        const modal = this.closest('.packify-modal');
        closeModal(modal);
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.packify-modal[aria-hidden="false"]').forEach(modal => {
            closeModal(modal);
        });
    }
});
</script>

</body>
</html>