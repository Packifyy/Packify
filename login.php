<?php
require_once __DIR__ . '/functions.php';

start_session_safe();

if (current_user() !== null) {
    $user = current_user();

if ($user !== null && ($user['role'] ?? '') === 'kurir') {
    header('Location: courier-dashboard.php');
} else {
    header('Location: customer-dashboard.php');
}

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
        $stmt = mysqli_prepare(
            $db,
            'SELECT id, password_hash, role FROM users WHERE email = ?'
        );

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

            if (($user['role'] ?? '') === 'kurir') {
                header('Location: courier-dashboard.php');
            } else {
                header('Location: customer-dashboard.php');
            }

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

    <title>Masuk — Packify</title>

    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

<div class="login-page">

    <a href="index.php" class="brand">
        Pack<span>ify</span>
    </a>

    <main class="login-wrapper">

        <header class="login-header">

            <div class="eyebrow">
                PACKIFY LOGISTICS
            </div>

            <h1>
                Selamat<br>
                datang.
            </h1>

            <p>
                Masuk ke akun Packify untuk mengelola
                pengiriman dan memantau paket Anda.
            </p>

        </header>

        <section class="login-card">

            <div class="role-badge">
                SECURE LOGIN
            </div>

            <?php if (!empty($errors)): ?>

                <div class="login-error">
                    <?= htmlspecialchars(
                        implode(' ', $errors),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <form
                method="post"
                action="login.php"
                novalidate
            >

                <?= csrf_field() ?>

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $oldEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        required
                    >

                </div>

                <div class="form-group">

                    <div class="password-label">

                        <label for="password">
                            Password
                        </label>

                    </div>

                    <div class="password-field">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                        >
                            LIHAT
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    class="login-button"
                >
                    <span>Masuk ke Packify</span>
                    <span>→</span>
                </button>

            </form>

        </section>

        <div class="login-footer">
            <span>Belum punya akun?</span>
            <a href="register.php">
                Daftar sekarang
            </a>
        </div>

        <a href="index.php" class="back-link">
            ← Kembali ke halaman utama
        </a>

    </main>

</div>

<script>
(function () {

    const password =
        document.getElementById('password');

    const toggle =
        document.getElementById('togglePassword');

    if (!password || !toggle) return;

    toggle.addEventListener('click', function () {

        const visible =
            password.type === 'text';

        password.type =
            visible ? 'password' : 'text';

        toggle.textContent =
            visible ? 'LIHAT' : 'SEMBUNYIKAN';

    });

})();
</script>

</body>
</html>