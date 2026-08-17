<?php

require_once __DIR__ . '/functions.php';

start_session_safe();

$user = current_user();

header('Content-Type: application/json');

if (
    $user === null ||
    ($user['role'] ?? '') !== 'kurir'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);

    exit;
}


$courierId = (int) ($user['id'] ?? 0);

$shipments = [];


$stmt = mysqli_prepare(
    $db,
    'SELECT
        b.id_barang,
        b.id_pengirim,
        b.nama_penerima,
        b.berat_barang_kg,
        b.jumlah_barang,
        b.alamat_tujuan,
        b.status,
        b.id_kurir,
        b.created_at,
        u.nama AS nama_pengirim,
        u.alamat AS alamat_asal
     FROM barang b
     JOIN users u ON u.id = b.id_pengirim
     WHERE b.status IN ("belum_dikirim", "sedang_dikirim")
     AND (b.id_kurir IS NULL OR b.id_kurir = ?)
     ORDER BY b.id_barang DESC'
);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => mysqli_error($db)
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    'i',
    $courierId
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);


while (
    $row = mysqli_fetch_assoc($result)
) {

    $shipments[] = $row;
}


mysqli_stmt_close($stmt);


echo json_encode([
    'success' => true,
    'shipments' => $shipments
]);