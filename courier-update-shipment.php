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
        'UPDATE shipments
         SET status = "in_transit"
         WHERE id = ?
         AND status = "pending"'
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
                'Shipment sudah diambil atau tidak tersedia.'
        ]);

        exit;
    }


    mysqli_stmt_close($stmt);


    echo json_encode([
        'success' => true,
        'status' => 'in_transit',
        'message' =>
            'Shipment berhasil diambil. Status sekarang In transit.'
    ]);

    exit;
}


/* =====================================================
   DELIVERED
===================================================== */

if ($action === 'delivered') {

    $stmt = mysqli_prepare(
        $db,
        'UPDATE shipments
         SET status = "delivered"
         WHERE id = ?
         AND status = "in_transit"'
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
                'Shipment belum berstatus In transit.'
        ]);

        exit;
    }


    mysqli_stmt_close($stmt);


    echo json_encode([
        'success' => true,
        'status' => 'delivered',
        'message' =>
            'Shipment berhasil ditandai sebagai delivered.'
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