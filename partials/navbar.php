<?php
// Make sure $user is defined before including this file
// $user should be passed from the parent page

// If $user is not defined, try to get it from session
if (!isset($user)) {
    // You might want to check if user is logged in
    // This assumes you have a function to get user from session
    if (isset($_SESSION['user_id'])) {
        // Fetch user data from database or use stored session data
        // For simplicity, using session data if stored
        $user = $_SESSION['user'] ?? null;
    }
}

// Determine dashboard URL based on user role
$dashboard_url = 'dashboard.php'; // default fallback
if (isset($user['role'])) {
    if ($user['role'] === 'pelanggan') {
        $dashboard_url = 'dashboard-pelanggan.php';
    } elseif ($user['role'] === 'kurir') {
        $dashboard_url = 'dashboard-kurir.php';
    }
}
?>
<nav class="navbar navbar-expand navbar-light bg-white border-bottom px-3">
    <span class="navbar-brand fw-bold">Packify</span>
    <div class="ms-auto d-flex align-items-center gap-3">
        <?php if (isset($user['nama'])): ?>
            <span class="text-muted small">Halo, <?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <a href="<?= $dashboard_url ?>" class="nav-link d-inline">Dashboard</a>
        <a href="profile.php" class="nav-link d-inline">Profil</a>
        <a href="logout.php" class="nav-link d-inline text-danger">Logout</a>
    </div>
</nav>