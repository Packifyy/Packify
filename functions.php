<?php

require_once __DIR__ . '/service/database.php';

function start_session_safe(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    global $db;
    start_session_safe();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = mysqli_prepare(
        $db,
        'SELECT id, nama, alamat, telpon, email, role, created_at FROM users WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user ?: null;
}

function require_login(array $allowedRoles = []): array
{
    $user = current_user();

    if ($user === null) {
        header('Location: login.php');
        exit;
    }

    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        die('Anda tidak punya akses ke halaman ini.');
    }

    return $user;
}

function login_user(int $userId): void
{
    start_session_safe();
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    start_session_safe();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain']);
    }
    session_destroy();
}

function csrf_token(): string
{
    start_session_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"$token\">";
}

function verify_csrf(): void
{
    start_session_safe();
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        die('Permintaan ditolak (CSRF token tidak valid).');
    }
}

function set_flash(string $type, string $message): void
{
    start_session_safe();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    start_session_safe();
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

const VALID_ROLES = ['pelanggan', 'kurir'];

function validate_register(array $data): array
{
    $errors = [];

    if (empty($data['nama']) || strlen($data['nama']) > 150) {
        $errors[] = 'Nama wajib diisi (maksimal 150 karakter).';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if (empty($data['role']) || !in_array($data['role'], VALID_ROLES, true)) {
        $errors[] = 'Role harus pelanggan atau kurir.';
    }
    if (empty($data['alamat'])) {
        $errors[] = 'Alamat wajib diisi.';
    }
    if (empty($data['telpon']) || strlen($data['telpon']) > 20) {
        $errors[] = 'Nomor telepon wajib diisi (maksimal 20 karakter).';
    } elseif (!preg_match('/^08[0-9]{8,11}$/', $data['telpon'])) {
        $errors[] = 'Nomor telepon harus diawali "08" dan berisi 10-13 digit angka saja.';
    }

    return $errors;
}

function validate_profile_update(array $data): array
{
    $errors = [];

    if (empty($data['nama']) || strlen($data['nama']) > 150) {
        $errors[] = 'Nama wajib diisi (maksimal 150 karakter).';
    }
    if (empty($data['alamat'])) {
        $errors[] = 'Alamat wajib diisi.';
    }
    if (empty($data['telpon']) || strlen($data['telpon']) > 20) {
        $errors[] = 'Nomor telepon wajib diisi (maksimal 20 karakter).';
    }

    return $errors;
}

function validate_change_password(array $data): array
{
    $errors = [];

    if (empty($data['password_lama'])) {
        $errors[] = 'Password lama wajib diisi.';
    }
    if (empty($data['password_baru']) || strlen($data['password_baru']) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }
    if (($data['konfirmasi_password_baru'] ?? '') !== ($data['password_baru'] ?? '')) {
        $errors[] = 'Konfirmasi password baru tidak sama dengan password baru.';
    }

    return $errors;
}