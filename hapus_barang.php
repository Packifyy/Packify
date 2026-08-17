<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$id = (int) ($_GET['id'] ?? 0);

/* ===============================================================================
 * INTENTIONALLY VULNERABLE - TRAINING LAB (CYBERSECURITY ASSESSMENT)
 * Layer: Application Layer
 * Vulnerability: IDOR (Insecure Direct Object Reference)
 * 
 * Query SELECT dan DELETE di bawah ini TIDAK memvalidasi kepemilikan paket
 * terhadap user yang sedang login (`id_pengirim = $user['id']`).
 * Akibatnya, pelanggan A dapat menghapus / membatalkan paket milik pelanggan B
 * hanya dengan mengganti parameter `?id=...` di URL.
 * =============================================================================== */

$stmt = mysqli_prepare($db, 'SELECT * FROM barang WHERE id_barang = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$paket = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($paket && $paket['status'] === 'belum_dikirim') {
    // VULN IDOR: Query DELETE tidak memvalidasi id_pengirim
    $stmt = mysqli_prepare($db, 'DELETE FROM barang WHERE id_barang = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    set_flash('success', 'Paket berhasil dibatalkan.');
} else {
    set_flash('danger', 'Paket tidak ditemukan atau tidak dapat dibatalkan.');
}

header('Location: package.php');
exit;