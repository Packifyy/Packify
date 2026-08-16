<?php
require_once __DIR__ . '/functions.php';

$user = require_login(['pelanggan']);

$id = (int) ($_GET['id'] ?? 0);

/* ====================== CATATAN VULNERABILITY (SENGAJA) ======================
 * IDOR — sama seperti edit_barang.php, query di bawah ini SENGAJA tidak
 * memvalidasi id_pengirim pemilik paket terhadap user yang sedang login.
 * Pelanggan mana pun yang login bisa menghapus paket pelanggan lain hanya
 * dengan mengganti ?id= di URL.
 *
 * Versi aman: DELETE FROM barang WHERE id_barang = ? AND id_pengirim = ?
 * =============================================================================== */

$stmt = mysqli_prepare($db, 'SELECT * FROM barang WHERE id_barang = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$paket = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($paket && $paket['status'] === 'belum_dikirim') {
    // Sengaja TIDAK ada "AND id_pengirim = ?" di WHERE (vuln IDOR, lihat catatan di atas)
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
