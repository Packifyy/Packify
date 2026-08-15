<?php
require_once __DIR__ . '/functions.php';

start_session_safe();
if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($oldEmail) || empty($password)) {
        $errors[] = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($db, 'SELECT id, password_hash FROM users WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $oldEmail);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email atau password salah.';
        } else {
            login_user((int) $user['id']);
            set_flash('success', 'Berhasil masuk.');
            header('Location: dashboard.php');
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
    <title>Masuk - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Lstyle.css">
</head>
<body>
    <div class="auth-card">
        <h1>Masuk</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>

        <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>
</html>