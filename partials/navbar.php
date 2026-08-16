<nav class="navbar navbar-expand navbar-light bg-white border-bottom px-3">
    <span class="navbar-brand fw-bold">Packify</span>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small">Halo, <?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="dashboard.php" class="nav-link d-inline">Dashboard</a>
        <a href="profile.php" class="nav-link d-inline">Profil</a>
        <a href="edit.php" class="nav-link d-inline">Edit Akun</a>
        <a href="logout.php" class="nav-link d-inline text-danger">Logout</a>
    </div>
</nav>