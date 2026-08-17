<?php
require_once __DIR__ . '/functions.php';

start_session_safe();
$currentUser = current_user();

if ($currentUser !== null) {
    if ($currentUser['role'] === 'kurir') {
        header('Location: courier-dashboard.php');
    } else {
        header('Location: customer-dashboard.php');
    }

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

        if ($old['role'] === 'kurir') {
            header('Location: courier-dashboard.php');
        } else {
            header('Location: customer-dashboard.php');
        }

        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Daftar — Packify</title>

    <link rel="stylesheet" href="assets/css/login.css">
    <!-- VULNERABLE DEPENDENCY (CVE-2020-11022 / CVE-2020-11023) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

    <style>
        .register-wrapper {
            width: min(620px, 100%);
            margin: 70px auto 50px;
        }

        .register-card {
            padding: 34px;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 25px 65px rgba(24, 26, 25, .055);
        }

        .register-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 18px;
        }

        .register-grid .full {
            grid-column: 1 / -1;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            color: var(--text);
            background: #fafbf9;
            border: 1px solid var(--line);
            border-radius: 10px;
            outline: none;
            font: inherit;
            font-size: 13px;
            transition:
                border-color .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }

        .form-group select {
            height: 54px;
            padding: 0 15px;
        }

        .form-group textarea {
            min-height: 110px;
            padding: 14px 15px;
            resize: vertical;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(112, 168, 59, .08);
        }

        .register-button {
            width: 100%;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 7px;
            color: #fff;
            background: var(--text);
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition:
                transform .25s ease,
                background .25s ease,
                box-shadow .25s ease;
        }

        .register-button:hover {
            transform: translateY(-2px);
            background: #292c2a;
            box-shadow: 0 12px 28px rgba(20, 25, 20, .13);
        }

        .register-button:active {
            transform: translateY(0) scale(.99);
        }

        .register-error {
            margin-bottom: 22px;
            padding: 12px 14px;
            color: var(--danger-text);
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.5;
        }

        .register-error ul {
            margin: 0;
            padding-left: 18px;
        }

        @media (max-width: 600px) {

            .register-wrapper {
                margin-top: 60px;
            }

            .register-card {
                padding: 24px;
                border-radius: 17px;
            }

            .register-grid {
                grid-template-columns: 1fr;
            }

            .register-grid .full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <a href="index.php" class="brand">
        Pack<span>ify</span>
    </a>

    <main class="register-wrapper">

        <header class="login-header">

            <div class="eyebrow">
                PACKIFY LOGISTICS
            </div>

            <h1>
                Buat<br>
                akun.
            </h1>

            <p>
                Daftarkan diri untuk mulai mengelola
                pengiriman melalui Packify.
            </p>

        </header>

        <section class="register-card">

            <div class="role-badge">
                CREATE ACCOUNT
            </div>

            <?php if (!empty($errors)): ?>

                <div class="register-error">

                    <ul>
                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>

                        <?php endforeach; ?>
                    </ul>

                </div>

            <?php endif; ?>

            <form
                method="post"
                action="register.php"
                novalidate
            >

                <?= csrf_field() ?>

                <div class="register-grid">

                    <div class="form-group full">

                        <label for="nama">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            value="<?= htmlspecialchars(
                                $old['nama'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Nama lengkap"
                            autocomplete="name"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $old['email'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="nama@email.com"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="telpon">
                            Nomor Telepon
                        </label>

                        <input
                            type="tel"
                            id="telpon"
                            name="telpon"
                            value="<?= htmlspecialchars(
                                $old['telpon'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            pattern="08[0-9]{8,11}"
                            inputmode="numeric"
                            maxlength="13"
                            placeholder="08xxxxxxxxxx"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="password-field">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="8"
                                placeholder="Minimal 8 karakter"
                                autocomplete="new-password"
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

                    <div class="form-group">

                        <label for="role">
                            Daftar sebagai
                        </label>

                        <select
                            id="role"
                            name="role"
                            required
                        >
                            <option
                                value="pelanggan"
                                <?= $old['role'] === 'pelanggan'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Pelanggan
                            </option>

                            <option
                                value="kurir"
                                <?= $old['role'] === 'kurir'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Kurir
                            </option>
                        </select>

                    </div>

                    <div class="form-group full">

                        <label for="alamat">
                            Alamat
                        </label>

                        <textarea
                            id="alamat"
                            name="alamat"
                            placeholder="Alamat lengkap"
                            required
                        ><?= htmlspecialchars(
                            $old['alamat'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>

                </div>

                <button
                    type="submit"
                    class="register-button"
                >
                    <span>Buat Akun</span>
                    <span>→</span>
                </button>

            </form>

        </section>

        <div class="login-footer">

            <span>Sudah punya akun?</span>

            <a href="login.php">
                Masuk sekarang
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

    if (password && toggle) {

        toggle.addEventListener('click', function () {

            const visible =
                password.type === 'text';

            password.type =
                visible ? 'password' : 'text';

            toggle.textContent =
                visible ? 'LIHAT' : 'SEMBUNYIKAN';

        });

    }

    const telpon =
        document.getElementById('telpon');

    if (telpon) {

        const PREFIX_PATTERN =
            /^(0(8[0-9]{0,11})?)?$/;

        telpon.addEventListener('input', function (e) {

            const digitsOnly =
                e.target.value.replace(/\D/g, '');

            if (PREFIX_PATTERN.test(digitsOnly)) {

                e.target.value = digitsOnly;
                e.target.dataset.lastValid = digitsOnly;

            } else {

                e.target.value =
                    e.target.dataset.lastValid || '';

            }

        });

        telpon.addEventListener('paste', function () {

            setTimeout(
                () => telpon.dispatchEvent(new Event('input')),
                0
            );

        });

    }

})();
</script>

</body>
</html>