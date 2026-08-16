<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$errors = [];
$old = ['nama_penerima' => '', 'alamat_tujuan' => '', 'berat_barang_kg' => '', 'jumlah_barang' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old = [
        'nama_penerima' => trim($_POST['nama_penerima'] ?? ''),
        'alamat_tujuan' => trim($_POST['alamat_tujuan'] ?? ''),
        'berat_barang_kg' => $_POST['berat_barang_kg'] ?? '',
        'jumlah_barang' => $_POST['jumlah_barang'] ?? '',
    ];

    $berat = (int) $old['berat_barang_kg'];
    $jumlah = (int) $old['jumlah_barang'];

    if ($old['nama_penerima'] === '' || strlen($old['nama_penerima']) > 150) {
        $errors[] = 'Nama penerima wajib diisi (maksimal 150 karakter).';
    }
    if ($old['alamat_tujuan'] === '') {
        $errors[] = 'Alamat tujuan wajib diisi.';
    }
    if ($berat <= 0) {
        $errors[] = 'Berat barang harus lebih dari 0.';
    }
    if ($jumlah <= 0) {
        $errors[] = 'Jumlah barang harus lebih dari 0.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $db,
            'INSERT INTO barang (id_pengirim, nama_penerima, berat_barang_kg, jumlah_barang, alamat_tujuan, status)
             VALUES (?, ?, ?, ?, ?, "belum_dikirim")'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'isiis',
            $user['id'],
            $old['nama_penerima'],
            $berat,
            $jumlah,
            $old['alamat_tujuan']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        set_flash('success', 'Paket berhasil ditambahkan.');
        header('Location: package.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Paket - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="barang.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h4 class="fw-bold mb-3">Tambah Paket Baru</h4>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="barang.php" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="nama_penerima" class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima"
                           value="<?= htmlspecialchars($old['nama_penerima'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="alamat_tujuan" class="form-label">Alamat Tujuan</label>
                    <textarea class="form-control" id="alamat_tujuan" name="alamat_tujuan" rows="3" required><?= htmlspecialchars($old['alamat_tujuan'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="berat_barang_kg" class="form-label">Berat Barang (kg)</label>
                    <input type="number" class="form-control" id="berat_barang_kg" name="berat_barang_kg" min="1"
                           value="<?= htmlspecialchars((string) $old['berat_barang_kg'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="jumlah_barang" class="form-label">Jumlah Barang</label>
                    <input type="number" class="form-control" id="jumlah_barang" name="jumlah_barang" min="1"
                           value="<?= htmlspecialchars((string) $old['jumlah_barang'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Kirim Paket</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
