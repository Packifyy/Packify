<?php

require_once __DIR__ . '/functions.php';

$user = require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $errors[] = 'Masukkan password Anda untuk konfirmasi.';
    } else {
        $stmt = mysqli_prepare($db, 'SELECT password_hash FROM users WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $user['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!password_verify($password, $row['password_hash'])) {
            $errors[] = 'Password salah. Akun tidak dihapus.';
        } else {
            $stmt = mysqli_prepare($db, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            logout_user();
            start_session_safe();
            set_flash('success', 'Akun Anda telah dihapus.');
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hapus Akun - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card border-danger" style="max-width: 480px;">
            <div class="card-body">
                <h1 class="h5 text-danger mb-3">Hapus Akun</h1>
                <p>Tindakan ini <strong>permanen</strong> dan tidak bisa dibatalkan. Masukkan password Anda untuk melanjutkan.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="delete.php" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Ya, Hapus Akun Saya</button>
                    <a href="edit.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>