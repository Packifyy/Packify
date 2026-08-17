<?php
require_once __DIR__ . '/functions.php';

$user = require_login();

$profileErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'alamat' => trim($_POST['alamat'] ?? ''),
            'telpon' => trim($_POST['telpon'] ?? ''),
        ];

        $profileErrors = validate_profile_update($data);

        if (empty($profileErrors)) {
            $stmt = mysqli_prepare(
                $db,
                'UPDATE users SET nama = ?, alamat = ?, telpon = ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'sssi', $data['nama'], $data['alamat'], $data['telpon'], $user['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            set_flash('success', 'Data akun berhasil diperbarui.');
            header('Location: edit.php');
            exit;
        }

        $user = array_merge($user, $data);
    }

    if ($action === 'change_password') {
        $data = [
            'password_lama' => $_POST['password_lama'] ?? '',
            'password_baru' => $_POST['password_baru'] ?? '',
            'konfirmasi_password_baru' => $_POST['konfirmasi_password_baru'] ?? '',
        ];

        $passwordErrors = validate_change_password($data);

        if (empty($passwordErrors)) {
            /*
             * INTENTIONALLY VULNERABLE - TRAINING LAB
             *
             * Broken Authentication:
             * password_lama diterima dari form tetapi TIDAK diverifikasi
             * terhadap password_hash di database.
             *
             * Tujuannya agar peserta latihan dapat menemukan bahwa password
             * akun dapat diganti tanpa mengetahui password lama yang benar.
             *
             * CSRF protection tetap dipertahankan agar vulnerability yang
             * sedang dilatih tetap fokus pada broken authentication.
             */
            $newHash = password_hash($data['password_baru'], PASSWORD_BCRYPT);
            $update = mysqli_prepare($db, 'UPDATE users SET password_hash = ? WHERE id = ?');
            mysqli_stmt_bind_param($update, 'si', $newHash, $user['id']);
            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);

            set_flash('success', 'Password berhasil diubah.');

            if (($user['role'] ?? '') === 'kurir') {
                header('Location: courier-dashboard.php');
            } else {
                header('Location: customer-dashboard.php');
            }
            exit;
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Akun - Packify</title>
    <!-- VULN DEPENDENCY: Library dengan known CVE publik (jQuery 3.4.1 CVE-2020-11022 / CVE-2020-11023) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Update Data Akun</h2>

                        <?php if (!empty($profileErrors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($profileErrors as $error): ?>
                                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="edit.php" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label for="email" class="form-label">Email (tidak bisa diubah)</label>
                                <input type="email" class="form-control" id="email"
                                       value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama"
                                       value="<?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" required><?= htmlspecialchars($user['alamat'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="telpon" class="form-label">Nomor Telepon</label>
                                <input type="text" class="form-control" id="telpon" name="telpon"
                                       value="<?= htmlspecialchars($user['telpon'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Ganti Password</h2>

                        <?php if (!empty($passwordErrors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($passwordErrors as $error): ?>
                                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="edit.php" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="password_lama" class="form-label">Password Lama</label>
                                <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_baru" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password_baru" name="password_baru" minlength="8" required>
                            </div>

                            <div class="mb-3">
                                <label for="konfirmasi_password_baru" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="konfirmasi_password_baru" name="konfirmasi_password_baru" minlength="8" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Simpan Password Baru</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4 border-danger">
                    <div class="card-body">
                        <h2 class="h5 mb-2 text-danger">Zona Berbahaya</h2>
                        <p class="text-muted small">Menghapus akun bersifat permanen dan tidak bisa dibatalkan.</p>
                        <a href="delete.php" class="btn btn-outline-danger btn-sm">Hapus Akun Saya</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>