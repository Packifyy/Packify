<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$id = (int) ($_GET['id'] ?? 0);

/* ====================== FIX: IDOR (Insecure Direct Object Reference) ======================
 * SELECT & DELETE di bawah ini WAJIB memvalidasi id_pengirim pemilik paket terhadap
 * user yang sedang login, supaya pelanggan tidak bisa menghapus paket pelanggan lain
 * hanya dengan mengganti ?id= di URL.
 * =============================================================================== */

$stmt = mysqli_prepare($db, 'SELECT * FROM barang WHERE id_barang = ? AND id_pengirim = ?');
mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$paket = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($paket && $paket['status'] === 'belum_dikirim') {
    $stmt = mysqli_prepare($db, 'DELETE FROM barang WHERE id_barang = ? AND id_pengirim = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    set_flash('success', 'Paket berhasil dibatalkan.');
} else {
    set_flash('danger', 'Paket tidak ditemukan atau tidak dapat dibatalkan.');
}

header('Location: package.php');
exit;