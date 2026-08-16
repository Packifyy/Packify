<?php
require_once __DIR__ . '/functions.php';

$user = require_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - Packify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card" style="max-width: 480px;">
            <div class="card-body">
                <h1 class="h4 mb-3">Profil Saya</h1>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Nama</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Alamat</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['alamat'], ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Telepon</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['telpon'], ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>

                <a href="edit.php" class="btn btn-primary mt-3">Edit Profil / Ganti Password</a>
            </div>
        </div>
    </div>
</body>
</html>