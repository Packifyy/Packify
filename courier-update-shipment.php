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


$courierId =
    (int) ($user['id'] ?? 0);

$shipmentId =
    (int) ($_POST['shipment_id'] ?? 0);

$action =
    $_POST['action'] ?? '';


if ($shipmentId < 1) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Shipment ID tidak valid.'
    ]);

    exit;
}


/* =====================================================
   PICKUP
===================================================== */

if ($action === 'pickup') {

    $stmt = mysqli_prepare(
        $db,
        'UPDATE barang
         SET status = "sedang_dikirim",
             id_kurir = ?
         WHERE id_barang = ?
         AND status = "belum_dikirim"
         AND id_kurir IS NULL'
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
        'ii',
        $courierId,
        $shipmentId
    );


    mysqli_stmt_execute($stmt);


    if (
        mysqli_stmt_affected_rows($stmt) < 1
    ) {

        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => false,
            'message' =>
                'Barang sudah diambil atau tidak tersedia.'
        ]);

        exit;
    }


    mysqli_stmt_close($stmt);


    echo json_encode([
        'success' => true,
        'status' => 'sedang_dikirim',
        'message' =>
            'Barang berhasil diambil. Status sekarang sedang dikirim.'
    ]);

    exit;
}


/* =====================================================
   DELIVERED
===================================================== */

if ($action === 'delivered') {

    $stmt = mysqli_prepare(
        $db,
        'UPDATE barang
         SET status = "sudah_sampai"
         WHERE id_barang = ?
         AND status = "sedang_dikirim"
         AND id_kurir = ?'
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
        'ii',
        $shipmentId,
        $courierId
    );


    mysqli_stmt_execute($stmt);


    if (
        mysqli_stmt_affected_rows($stmt) < 1
    ) {

        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => false,
            'message' =>
                'Barang belum berstatus sedang dikirim atau bukan milik Anda.'
        ]);

        exit;
    }


    mysqli_stmt_close($stmt);


    echo json_encode([
        'success' => true,
        'status' => 'sudah_sampai',
        'message' =>
            'Barang berhasil ditandai sebagai sudah sampai.'
    ]);

    exit;
}


/* =====================================================
   INVALID ACTION
===================================================== */

http_response_code(400);

echo json_encode([
    'success' => false,
    'message' => 'Action tidak dikenal.'
]);