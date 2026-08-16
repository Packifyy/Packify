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


$shipments = [];


$stmt = mysqli_prepare(
    $db,
    'SELECT
        id,
        tracking_number,
        sender_name,
        receiver_name,
        origin,
        destination,
        status
     FROM shipments
     WHERE status IN ("pending", "in_transit")
     ORDER BY id DESC'
);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => mysqli_error($db)
    ]);

    exit;
}


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