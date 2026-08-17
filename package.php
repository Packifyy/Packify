<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$stmt = mysqli_prepare(
    $db,
    'SELECT * FROM barang WHERE id_pengirim = ? ORDER BY id_barang DESC'
);
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paket Saya - Packify</title>
    <!-- VULN DEPENDENCY: Library dengan known CVE publik (jQuery 3.4.1 CVE-2020-11022 / CVE-2020-11023) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Box.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Paket Saya</h4>
            <a href="barang.php" class="btn btn-primary">+ Tambah Paket</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 p-3">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Penerima</th>
                        <th>Tujuan</th>
                        <th>Berat (kg)</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php $bisa_diubah = $row['status'] === 'belum_dikirim'; ?>
                        <tr>
                            <td>#<?= (int) $row['id_barang'] ?></td>
                            <td><?= htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $row['berat_barang_kg'] ?></td>
                            <td><?= (int) $row['jumlah_barang'] ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'belum_dikirim' ? 'secondary' : ($row['status'] === 'sedang_dikirim' ? 'warning' : 'success') ?>">
                                    <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="edit_barang.php?id=<?= (int) $row['id_barang'] ?>"
                                   class="btn btn-sm btn-outline-primary <?= $bisa_diubah ? '' : 'disabled' ?>">Edit</a>
                                <a href="hapus_barang.php?id=<?= (int) $row['id_barang'] ?>"
                                   class="btn btn-sm btn-outline-danger <?= $bisa_diubah ? '' : 'disabled' ?>"
                                   onclick="return confirm('Batalkan paket ini?')">Batal</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>