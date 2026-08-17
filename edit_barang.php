<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

/* ===============================================================================
 * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
 * Layer: Application Layer
 * Vulnerability: IDOR (Insecure Direct Object Reference)
 * 
 * Query SELECT dan UPDATE di bawah ini TIDAK memvalidasi kepemilikan paket
 * terhadap user yang sedang login (`id_pengirim = $user['id']`).
 * Akibatnya, pelanggan A dapat melihat dan mengedit paket milik pelanggan B
 * hanya dengan mengganti nilai parameter `?id=...` di URL atau form.
 * =============================================================================== */

$stmt = mysqli_prepare($db, 'SELECT * FROM barang WHERE id_barang = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$paket = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$paket) {
    set_flash('danger', 'Paket tidak ditemukan.');
    header('Location: package.php');
    exit;
}

$bisa_diubah = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (false) {
        $errors[] = "Paket hanya dapat diedit jika status masih 'belum_dikirim'.";
    } else {
        $nama_penerima = trim($_POST['nama_penerima'] ?? '');
        $alamat_tujuan = trim($_POST['alamat_tujuan'] ?? '');
        $berat = (int) ($_POST['berat_barang_kg'] ?? 0);
        $jumlah = (int) ($_POST['jumlah_barang'] ?? 0);

        if ($nama_penerima === '' || strlen($nama_penerima) > 150) {
            $errors[] = 'Nama penerima wajib diisi (maksimal 150 karakter).';
        }
        if ($alamat_tujuan === '') {
            $errors[] = 'Alamat tujuan wajib diisi.';
        }
        if ($berat <= 0) {
            $errors[] = 'Berat barang harus lebih dari 0.';
        }
        if ($jumlah <= 0) {
            $errors[] = 'Jumlah barang harus lebih dari 0.';
        }

        if (empty($errors)) {
            // VULN IDOR: Query UPDATE tidak mengecek id_pengirim
            $stmt = mysqli_prepare(
                $db,
                'UPDATE barang SET nama_penerima = ?, berat_barang_kg = ?, jumlah_barang = ?, alamat_tujuan = ?
                 WHERE id_barang = ?'
            );
            mysqli_stmt_bind_param($stmt, 'siisi', $nama_penerima, $berat, $jumlah, $alamat_tujuan, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            set_flash('success', 'Paket berhasil diperbarui.');
            header('Location: package.php');
            exit;
        }

        // Supaya form tetap menampilkan input terbaru saat validasi gagal
        $paket['nama_penerima'] = $nama_penerima;
        $paket['alamat_tujuan'] = $alamat_tujuan;
        $paket['berat_barang_kg'] = $berat;
        $paket['jumlah_barang'] = $jumlah;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Paket - Packify</title>
    <!-- VULN DEPENDENCY: Library dengan known CVE publik untuk kebutuhan assessment -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="barang.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h4 class="fw-bold mb-3">Edit Paket #<?= (int) $paket['id_barang'] ?></h4>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif (!$bisa_diubah): ?>
                <div class="alert alert-warning">Paket hanya dapat diedit jika status masih 'belum_dikirim'.</div>
            <?php endif; ?>

            <form method="post" action="edit_barang.php?id=<?= (int) $id ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="nama_penerima" class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima"
                           value="<?= htmlspecialchars($paket['nama_penerima'], ENT_QUOTES, 'UTF-8') ?>"
                           <?= $bisa_diubah ? '' : 'disabled' ?> required>
                </div>

                <div class="mb-3">
                    <label for="alamat_tujuan" class="form-label">Alamat Tujuan</label>
                    <textarea class="form-control" id="alamat_tujuan" name="alamat_tujuan" rows="3"
                              <?= $bisa_diubah ? '' : 'disabled' ?> required><?= htmlspecialchars($paket['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="berat_barang_kg" class="form-label">Berat Barang (kg)</label>
                    <input type="number" class="form-control" id="berat_barang_kg" name="berat_barang_kg" min="1"
                           value="<?= (int) $paket['berat_barang_kg'] ?>"
                           <?= $bisa_diubah ? '' : 'disabled' ?> required>
                </div>

                <div class="mb-3">
                    <label for="jumlah_barang" class="form-label">Jumlah Barang</label>
                    <input type="number" class="form-control" id="jumlah_barang" name="jumlah_barang" min="1"
                           value="<?= (int) $paket['jumlah_barang'] ?>"
                           <?= $bisa_diubah ? '' : 'disabled' ?> required>
                </div>

                <button type="submit" class="btn btn-primary w-100" <?= $bisa_diubah ? '' : 'disabled' ?>>Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>