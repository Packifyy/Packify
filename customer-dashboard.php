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

// AJAX Track API for real-time tracking of any package across entire database
if (isset($_GET['action']) && $_GET['action'] === 'track_api') {
    header('Content-Type: application/json');
    $trackQuery = trim($_GET['q'] ?? '');
    $digits = preg_replace('/[^0-9]/', '', $trackQuery);
    $shipId = $digits ? (int)$digits : 0;
    
    if ($shipId > 0) {
        $stmt = mysqli_prepare($db, 'SELECT b.*, u.nama as nama_pengirim FROM barang b JOIN users u ON u.id = b.id_pengirim WHERE b.id_barang = ?');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $shipId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            
            if ($item) {
                echo json_encode(['status' => 'success', 'data' => $item]);
                exit;
            }
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Shipment not found']);
    exit;
}

// POST actions CSRF verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

// Handle change password
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

    /* ===============================================================================
     * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
     * Vulnerability: Broken Authentication
     * password_lama diterima dari form modal Settings tetapi TIDAK diverifikasi
     * terhadap password_hash di database via password_verify().
     * Pengguna dapat memasukkan sembarang teks pada kolom password lama.
     * =============================================================================== */
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
        set_flash('danger', 'Data profile tidak lengkap.');
        header('Location: customer-dashboard.php');
        exit;
    }

    $stmt = mysqli_prepare($db, 'UPDATE users SET nama = ?, telpon = ?, alamat = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sssi', $newName, $newPhone, $newAddress, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    set_flash('success', 'Profil berhasil diperbarui.');
    header('Location: customer-dashboard.php');
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
        set_flash('danger', 'Data barang tidak lengkap.');
        header('Location: customer-dashboard.php');
        exit;
    }

    $stmt = mysqli_prepare($db, 'INSERT INTO barang (id_pengirim, nama_penerima, berat_barang_kg, jumlah_barang, alamat_tujuan, status) VALUES (?, ?, ?, ?, ?, ?)');
    
    if (!$stmt) {
        set_flash('danger', 'Prepare statement gagal: ' . mysqli_error($db));
        header('Location: customer-dashboard.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'isiiss', $user['id'], $namaPenerima, $beratBarang, $jumlahBarang, $alamatTujuan, $status);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        set_flash('danger', 'Gagal membuat shipment: ' . htmlspecialchars($error));
        header('Location: customer-dashboard.php');
        exit;
    }

    $createdId = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);
    set_flash('success', 'Paket PKF-' . str_pad((string)$createdId, 4, '0', STR_PAD_LEFT) . ' berhasil dibuat!');
    header('Location: customer-dashboard.php');
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
        set_flash('danger', 'Data barang tidak lengkap.');
        header('Location: customer-dashboard.php');
        exit;
    }

    /* ===============================================================================
     * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
     * Vulnerability: IDOR (Insecure Direct Object Reference)
     * Query UPDATE di bawah ini TIDAK memvalidasi id_pengirim terhadap user yang sedang login.
     * Pelanggan B dapat mengedit paket milik siapapun hanya dengan mengirim id_barang target.
     * =============================================================================== */
    $stmt = mysqli_prepare($db, 'UPDATE barang SET nama_penerima = ?, alamat_tujuan = ?, berat_barang_kg = ?, jumlah_barang = ? WHERE id_barang = ?');
    
    if (!$stmt) {
        set_flash('danger', 'Prepare statement gagal: ' . mysqli_error($db));
        header('Location: customer-dashboard.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ssiii', $namaPenerima, $alamatTujuan, $beratBarang, $jumlahBarang, $idBarang);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected >= 0) {
        set_flash('success', 'Paket PKF-' . str_pad((string)$idBarang, 4, '0', STR_PAD_LEFT) . ' berhasil diperbarui!');
    } else {
        set_flash('danger', 'Gagal memperbarui paket.');
    }

    header('Location: customer-dashboard.php?view_id=' . $idBarang);
    exit;
}

// Handle cancel shipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_shipment') {
    $idBarang = (int) ($_POST['id_barang'] ?? 0);

    if ($idBarang < 1) {
        set_flash('danger', 'ID barang tidak valid.');
        header('Location: customer-dashboard.php');
        exit;
    }

    /* ===============================================================================
     * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
     * Vulnerability: IDOR (Insecure Direct Object Reference)
     * Query DELETE di bawah ini TIDAK memvalidasi id_pengirim terhadap user yang sedang login.
     * =============================================================================== */
    $stmt = mysqli_prepare($db, 'DELETE FROM barang WHERE id_barang = ?');
    mysqli_stmt_bind_param($stmt, 'i', $idBarang);
    mysqli_stmt_execute($stmt);
    $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($berhasil) {
        set_flash('success', 'Paket PKF-' . str_pad((string)$idBarang, 4, '0', STR_PAD_LEFT) . ' berhasil dibatalkan.');
    } else {
        set_flash('danger', 'Paket tidak ditemukan atau sudah tidak dapat dibatalkan.');
    }

    header('Location: customer-dashboard.php');
    exit;
}

// Fetch customer shipments (milik user yang sedang login)
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

// Check for URL query parameter for IDOR editing (e.g. ?shipment=1 or ?id=1 or ?edit_id=1)
$targetEditShipment = null;
$paramVal = $_GET['shipment'] ?? $_GET['edit_id'] ?? $_GET['id'] ?? $_GET['edit'] ?? 0;
$editId = is_numeric($paramVal) ? (int)$paramVal : (int)preg_replace('/[^0-9]/', '', (string)$paramVal);

if ($editId > 0 && !isset($_GET['view_id']) && !isset($_GET['view'])) {
    /* ===============================================================================
     * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
     * Vulnerability: IDOR (Insecure Direct Object Reference)
     * Query di bawah ini TIDAK memvalidasi kepemilikan id_pengirim terhadap user yang sedang login.
     * Pelanggan B dapat me-load data paket milik Pelanggan A hanya dengan memasukkan ?shipment=1 di URL.
     * =============================================================================== */
    $stmtEdit = mysqli_prepare($db, 'SELECT * FROM barang WHERE id_barang = ?');
    if ($stmtEdit) {
        mysqli_stmt_bind_param($stmtEdit, 'i', $editId);
        mysqli_stmt_execute($stmtEdit);
        $resEdit = mysqli_stmt_get_result($stmtEdit);
        $targetEditShipment = mysqli_fetch_assoc($resEdit);
        mysqli_stmt_close($stmtEdit);
    }
}

// Check for URL query parameter for IDOR viewing (e.g. ?view_id=1)
$targetViewShipment = null;
$viewParam = $_GET['view_id'] ?? $_GET['view'] ?? 0;
$viewId = is_numeric($viewParam) ? (int)$viewParam : (int)preg_replace('/[^0-9]/', '', (string)$viewParam);

if ($viewId > 0) {
    /* ===============================================================================
     * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
     * Vulnerability: IDOR (Insecure Direct Object Reference)
     * =============================================================================== */
    $stmtView = mysqli_prepare($db, 'SELECT b.*, u.nama as nama_pengirim, u.alamat as alamat_asal FROM barang b JOIN users u ON u.id = b.id_pengirim WHERE b.id_barang = ?');
    if ($stmtView) {
        mysqli_stmt_bind_param($stmtView, 'i', $viewId);
        mysqli_stmt_execute($stmtView);
        $resView = mysqli_stmt_get_result($stmtView);
        $targetViewShipment = mysqli_fetch_assoc($resView);
        mysqli_stmt_close($stmtView);
    }
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
    <!-- VULNERABLE DEPENDENCY (CVE-2020-11022 / CVE-2020-11023) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
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
            <a href="javascript:void(0)" onclick="openModal(document.getElementById('profileModal'))">Settings</a>
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
            <div class="profile" onclick="openModal(document.getElementById('profileModal'))" title="Edit profile & password" style="cursor: pointer;">
                <div class="avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="profile-info">
                    <strong><?= htmlspecialchars($name) ?></strong>
                    <span>Customer</span>
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
            <section class="panel" id="shipments" data-reveal>
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
                        <?php foreach ($customerShipments as $shipment): ?>
                            <?php $formattedId = 'PKF-' . str_pad((string)$shipment['id_barang'], 4, '0', STR_PAD_LEFT); ?>
                            <div class="shipment-row">
                                <div class="shipment-main">
                                    <strong><?= htmlspecialchars($formattedId) ?></strong>
                                    <span><?= htmlspecialchars($shipment['nama_penerima']) ?> <small style="color: var(--muted, #767d74); font-size: 10px; font-weight: normal; margin-left: 4px;">(ID: <?= (int)$shipment['id_barang'] ?>)</small></span>
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
                                            data-formatted-id="<?= htmlspecialchars($formattedId, ENT_QUOTES) ?>"
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- RECENT SHIPMENT -->
            <div class="panel large-panel" data-reveal>
                <div class="panel-heading">
                    <div>
                        <span class="small-label">RECENT SHIPMENT</span>
                        <h2><?= $recentShipment ? 'PKF-' . str_pad((string)$recentShipment['id_barang'], 4, '0', STR_PAD_LEFT) : 'No shipments' ?></h2>
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
                <p>Enter your shipment ID or PKF code to see the latest status.</p>

                <form id="trackingForm" class="tracking-form">
                    <input type="text" name="tracking_id" placeholder="Shipment ID (e.g. PKF-0001 or 1)" autocomplete="off" maxlength="30" required>
                    <button type="submit">Track <span>→</span></button>
                </form>

                <div id="trackingResult" class="tracking-result"></div>

                <div class="tracking-tip">
                    <span class="tip-icon">i</span>
                    <div>
                        <strong>Tracking tip</strong>
                        <p>Search any shipment code (e.g. <code>PKF-0001</code>) or direct number (e.g. <code>1</code>) to track package status.</p>
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
                <a href="#shipments">View all →</a>
            </div>

            <?php 
            $historyShipments = array_slice($customerShipments, 0, 5);
            if (empty($historyShipments)): ?>
                <div class="empty-state" style="padding: 30px 0;">
                    <span>○</span>
                    <p>No shipment history yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($historyShipments as $historyIndex => $shipment): ?>
                    <?php $histFormattedId = 'PKF-' . str_pad((string)$shipment['id_barang'], 4, '0', STR_PAD_LEFT); ?>
                    <div class="history-row">
                        <div class="history-info">
                            <strong><?= htmlspecialchars($histFormattedId) ?></strong>
                            <span><?= htmlspecialchars($shipment['nama_penerima']) ?> · <?= (int)$shipment['berat_barang_kg'] ?> kg</span>
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
                <p>Create a new shipment order and our couriers will pick it up.</p>
            </div>
            <a href="logout.php" class="btn-primary">
                End session <span>→</span>
            </a>
        </section>

    </main>
</div>

<!-- PROFILE & PASSWORD MODAL -->
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

        <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid rgba(0,0,0,.08);">
            <div class="packify-modal-header" style="margin-bottom: 16px;">
                <div>
                    <span class="small-label">SECURITY</span>
                    <h2>Change password</h2>
                </div>
            </div>
            <form class="packify-form" id="passwordForm" method="POST" action="customer-dashboard.php">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
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

<!-- SHIPMENT FORM MODAL (CREATE & EDIT) -->
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
                <h2 id="detailShipmentId">PKF-0001</h2>
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
        <div class="form-actions" style="margin-top: 20px;">
            <button type="button" class="form-button secondary" data-close-modal>Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // New shipment button
    const newBtn = document.getElementById('newShipmentButton');
    if (newBtn) {
        newBtn.addEventListener('click', function() {
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
            const formattedId = this.dataset.formattedId || ('PKF-' + String(id).padStart(4, '0'));

            document.getElementById('detailShipmentId').textContent = formattedId;
            document.getElementById('detailStatus').textContent = this.dataset.status || 'Belum dikirim';
            document.getElementById('detailRecipient').textContent = this.dataset.namaPenerima || '-';
            document.getElementById('detailAddress').textContent = this.dataset.alamatTujuan || '-';
            document.getElementById('detailWeight').textContent = (this.dataset.beratBarang || '-') + ' kg';
            document.getElementById('detailQuantity').textContent = this.dataset.jumlahBarang || '1';
            document.getElementById('detailCreated').textContent = this.dataset.createdAt || '-';

            openModal(document.getElementById('shipmentDetailModal'));
        });
    });

    // Edit shipment button click handler
    document.querySelectorAll('[data-edit-shipment]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.editShipment;
            const namaPenerima = this.dataset.namaPenerima || '';
            const alamatTujuan = this.dataset.alamatTujuan || '';
            const beratBarang = this.dataset.beratBarang || '1';
            const jumlahBarang = this.dataset.jumlahBarang || '1';
            const formattedId = 'PKF-' + String(id).padStart(4, '0');
            
            const modal = document.getElementById('shipmentFormModal');
            const form = modal.querySelector('form');
            const title = modal.querySelector('h2');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            title.textContent = 'Edit shipment ' + formattedId;
            submitBtn.textContent = 'Update shipment';
            form.querySelector('input[name="action"]').value = 'update_shipment';
            
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
            
            // Sync URL parameter
            history.pushState(null, '', 'customer-dashboard.php?shipment=' + id);
            
            openModal(modal);
        });
    });

    // Cancel shipment button click handler
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

    // Reset modal and URL on close
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.packify-modal');
            closeModal(modal);
            if (window.location.search.includes('shipment') || window.location.search.includes('id') || window.location.search.includes('view')) {
                history.pushState(null, '', 'customer-dashboard.php');
            }
            if (modal && modal.id === 'shipmentFormModal') {
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

    // Auto-open edit modal if URL query parameter ?shipment=ID or ?edit_id=ID is present (IDOR testing)
    <?php if (!empty($targetEditShipment)): ?>
        (function() {
            const editTarget = <?= json_encode($targetEditShipment) ?>;
            const modal = document.getElementById('shipmentFormModal');
            const form = modal.querySelector('form');
            const title = modal.querySelector('h2');
            const submitBtn = form.querySelector('button[type="submit"]');
            const formattedId = 'PKF-' + String(editTarget.id_barang).padStart(4, '0');

            title.textContent = 'Edit shipment ' + formattedId;
            submitBtn.textContent = 'Update shipment';
            form.querySelector('input[name="action"]').value = 'update_shipment';

            let idField = form.querySelector('input[name="id_barang"]');
            if (!idField) {
                idField = document.createElement('input');
                idField.type = 'hidden';
                idField.name = 'id_barang';
                form.appendChild(idField);
            }
            idField.value = editTarget.id_barang;

            form.querySelector('input[name="nama_penerima"]').value = editTarget.nama_penerima || '';
            form.querySelector('textarea[name="alamat_tujuan"]').value = editTarget.alamat_tujuan || '';
            form.querySelector('input[name="berat_barang"]').value = editTarget.berat_barang_kg || 1;
            form.querySelector('input[name="jumlah_barang"]').value = editTarget.jumlah_barang || 1;

            openModal(modal);
        })();
    <?php endif; ?>

    // Auto-open view modal if URL query parameter ?view_id=ID is present
    <?php if (!empty($targetViewShipment)): ?>
        (function() {
            const viewTarget = <?= json_encode($targetViewShipment) ?>;
            const formattedId = 'PKF-' + String(viewTarget.id_barang).padStart(4, '0');

            document.getElementById('detailShipmentId').textContent = formattedId;
            document.getElementById('detailStatus').textContent = viewTarget.status || 'Belum dikirim';
            document.getElementById('detailRecipient').textContent = viewTarget.nama_penerima || '-';
            document.getElementById('detailAddress').textContent = viewTarget.alamat_tujuan || '-';
            document.getElementById('detailWeight').textContent = (viewTarget.berat_barang_kg || '1') + ' kg';
            document.getElementById('detailQuantity').textContent = (viewTarget.jumlah_barang || '1') + ' item';
            document.getElementById('detailCreated').textContent = viewTarget.created_at || '-';

            openModal(document.getElementById('shipmentDetailModal'));
        })();
    <?php endif; ?>

    // Tracking form handler (Real-time Database Lookup)
    document.getElementById('trackingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const rawInput = this.querySelector('input[name="tracking_id"]').value.trim();
        const resultDiv = document.getElementById('trackingResult');
        
        if (!rawInput) {
            resultDiv.innerHTML = '<p style="color: #e74c3c;">Please enter a shipment ID or PKF code.</p>';
            resultDiv.style.display = 'block';
            return;
        }

        resultDiv.innerHTML = '<p style="color: var(--muted, #767d74);">Searching database...</p>';
        resultDiv.style.display = 'block';

        fetch('customer-dashboard.php?action=track_api&q=' + encodeURIComponent(rawInput))
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const item = res.data;
                    const formattedId = 'PKF-' + String(item.id_barang).padStart(4, '0');
                    const status = item.status || 'belum_dikirim';
                    const isDelivered = status.toLowerCase() === 'sudah_sampai';
                    const isInTransit = status.toLowerCase() === 'sedang_dikirim';
                    
                    resultDiv.innerHTML = `
                        <div style="padding: 16px; background: var(--surface-soft, #f8f9fa); border: 1px solid var(--line, #e5e9e2); border-radius: 10px; margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong style="font-size: 14px; color: var(--text, #161c16);">${formattedId}</strong>
                                <span class="status ${isDelivered ? 'delivered' : (isInTransit ? 'in-transit' : '')}">${status.replace('_', ' ')}</span>
                            </div>
                            <p style="margin: 4px 0; font-size: 12px;"><strong>Sender:</strong> ${item.nama_pengirim || '-'}</p>
                            <p style="margin: 4px 0; font-size: 12px;"><strong>Recipient:</strong> ${item.nama_penerima || '-'}</p>
                            <p style="margin: 4px 0; font-size: 12px;"><strong>Address:</strong> ${item.alamat_tujuan || '-'}</p>
                            <p style="margin: 4px 0; font-size: 12px;"><strong>Package:</strong> ${(item.berat_barang_kg || '1')} kg · ${(item.jumlah_barang || '1')} item</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<p style="color: #e74c3c; margin-top: 12px;">Shipment <strong>${rawInput}</strong> not found in database.</p>`;
                }
            })
            .catch(() => {
                resultDiv.innerHTML = '<p style="color: #e74c3c; margin-top: 12px;">Failed to fetch shipment details.</p>';
            });
    });
});

// Modal functions
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
    if (window.location.search.includes('shipment') || window.location.search.includes('id') || window.location.search.includes('view')) {
        history.pushState(null, '', 'customer-dashboard.php');
    }
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