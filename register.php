<?php
require_once __DIR__ . '/functions.php';

start_session_safe();
if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = ['nama' => '', 'email' => '', 'role' => 'pelanggan', 'alamat' => '', 'telpon' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old = [
        'nama' => trim($_POST['nama'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => $_POST['role'] ?? '',
        'alamat' => trim($_POST['alamat'] ?? ''),
        'telpon' => trim($_POST['telpon'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';

    $errors = validate_register(array_merge($old, ['password' => $password]));

    if (empty($errors)) {
        $stmt = mysqli_prepare($db, 'SELECT id FROM users WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $old['email']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Email sudah terdaftar.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = mysqli_prepare(
            $db,
            'INSERT INTO users (nama, alamat, telpon, email, password_hash, role)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssss',
            $old['nama'],
            $old['alamat'],
            $old['telpon'],
            $old['email'],
            $passwordHash,
            $old['role']
        );
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        login_user((int) $newId);
        set_flash('success', 'Akun berhasil dibuat. Selamat datang di Packify!');
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Lstyle.css">
</head>
<body>
    <div class="auth-card">
        <h1>Daftar Akun</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama"
                       value="<?= htmlspecialchars($old['nama'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Daftar sebagai</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="pelanggan" <?= $old['role'] === 'pelanggan' ? 'selected' : '' ?>>Pelanggan</option>
                    <option value="kurir" <?= $old['role'] === 'kurir' ? 'selected' : '' ?>>Kurir</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" required><?= htmlspecialchars($old['alamat'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="telpon" class="form-label">Nomor Telepon</label>
                <input type="tel" class="form-control" id="telpon" name="telpon"
                       value="<?= htmlspecialchars($old['telpon'], ENT_QUOTES, 'UTF-8') ?>"
                       pattern="08[0-9]{8,11}" inputmode="numeric" maxlength="13"
                       placeholder="08xxxxxxxxxx"
                       title="Nomor telepon harus diawali 08 dan berisi 10-13 digit angka"
                       required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Daftar</button>
        </form>

        <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </div>

    <script>
        (function () {
            const telponInput = document.getElementById('telpon');
            const PREFIX_PATTERN = /^(0(8[0-9]{0,11})?)?$/;

            telponInput.addEventListener('input', function (e) {
                const digitsOnly = e.target.value.replace(/\D/g, '');

                if (PREFIX_PATTERN.test(digitsOnly)) {
                    e.target.value = digitsOnly;
                    e.target.dataset.lastValid = digitsOnly;
                } else {
                    e.target.value = e.target.dataset.lastValid || '';
                }
            });

            telponInput.addEventListener('paste', function (e) {
                setTimeout(() => telponInput.dispatchEvent(new Event('input')), 0);
            });
        })();
    </script>
</body>
</html>