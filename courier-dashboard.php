<?php

require_once __DIR__ . '/functions.php';

start_session_safe();

/* =====================================================
   COURIER ACCESS PROTECTION
===================================================== */

$user = current_user();

if (
    $user === null ||
    ($user['role'] ?? '') !== 'kurir'
) {
    header('Location: login.php?role=courier');
    exit;
}

$name = $user['nama'] ?? 'Courier';


/* =====================================================
   LOAD SHIPMENTS FROM DATABASE
===================================================== */

$courierId = (int) ($user['id'] ?? 0);

$courierShipments = [];

$stmt = mysqli_prepare(
    $db,
    "SELECT
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
     WHERE b.status IN ('belum_dikirim', 'sedang_dikirim')
     AND (b.id_kurir IS NULL OR b.id_kurir = ?)
     ORDER BY b.id_barang DESC"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $courierId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $courierShipments[] = $row;

    }

    mysqli_stmt_close($stmt);

}


/* =====================================================
   COURIER ACTION
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_shipment_status'
) {

    $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = [
        'sedang_dikirim',
        'sudah_sampai'
    ];

    if (
        $shipmentId < 1 ||
        !in_array($newStatus, $allowedStatuses, true)
    ) {
        die('Data barang tidak valid.');
    }


    if ($newStatus === 'sedang_dikirim') {

        $stmt = mysqli_prepare(
            $db,
            "UPDATE barang
             SET status = 'sedang_dikirim',
                 id_kurir = ?
             WHERE id_barang = ?
             AND status = 'belum_dikirim'
             AND id_kurir IS NULL"
        );

        if (!$stmt) {
            die(
                'Prepare statement gagal: ' .
                mysqli_error($db)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $courierId,
            $shipmentId
        );

    } else {

        $stmt = mysqli_prepare(
            $db,
            "UPDATE barang
             SET status = 'sudah_sampai'
             WHERE id_barang = ?
             AND status = 'sedang_dikirim'
             AND id_kurir = ?"
        );

        if (!$stmt) {
            die(
                'Prepare statement gagal: ' .
                mysqli_error($db)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $shipmentId,
            $courierId
        );

    }


    mysqli_stmt_execute($stmt);

    $berhasil = mysqli_stmt_affected_rows($stmt) > 0;

    mysqli_stmt_close($stmt);


    header(
        'Location: courier-dashboard.php?shipment=' .
        ($berhasil ? 'updated' : 'failed')
    );

    exit;
}


$firstName = explode(
    ' ',
    trim($name)
)[0];


$initial = strtoupper(
    substr(
        trim($name),
        0,
        1
    )
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Packify courier dashboard"
    >

    <title>Courier Dashboard — Packify</title>

    <link
        rel="stylesheet"
        href="assets/css/dashboard.css"
    >

</head>


<body>

<div class="dashboard courier-dashboard">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">

        <a
            href="index.php"
            class="dashboard-brand"
        >
            Pack<span>i</span>fy
        </a>


        <div class="sidebar-label">
            COURIER
        </div>


        <nav>

            <a
                class="active"
                href="#overview"
            >
                <span>01</span>
                Overview
            </a>

            <a href="#tasks">
                <span>02</span>
                Today's tasks
            </a>

            <a href="#deliveries">
                <span>03</span>
                Deliveries
            </a>

            <a href="#history">
                <span>04</span>
                Performance
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="#settings">
                Settings
            </a>

            <a href="logout.php">
                Log out
            </a>

        </div>

    </aside>



    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="dashboard-main">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header">

            <div class="dashboard-heading">

                <div class="small-label">
                    COURIER DASHBOARD
                </div>

                <h1 id="overview">
                    Good morning,
                    <?= htmlspecialchars($firstName) ?>.
                </h1>

                <p>
                    Here's your delivery route for today.
                </p>

            </div>


            <div class="profile">

                <div class="avatar">
                    <?= htmlspecialchars($initial) ?>
                </div>

                <div class="profile-info">

                    <strong>
                        <?= htmlspecialchars($name) ?>
                    </strong>

                    <span>
                        Courier
                    </span>

                </div>

            </div>

        </header>



        <!-- =================================================
             TODAY OVERVIEW
        ================================================== -->

        <section class="courier-overview-grid">


            <!-- WORKLOAD -->

            <div
                class="courier-hero"
                data-reveal
            >

                <div class="courier-hero-top">

                    <div>

                        <span class="small-label">
                            TODAY'S WORKLOAD
                        </span>

                        <h2>
                            Keep moving.
                        </h2>

                    </div>

                    <span class="live-status">
                        <i></i>
                        ACTIVE
                    </span>

                </div>


                <div class="workload-number">
                    <strong>06</strong>

                    <span>
                        stops<br>
                        scheduled
                    </span>
                </div>


                <div class="workload-progress">

                    <div class="workload-progress-head">

                        <span>
                            Daily completion
                        </span>

                        <strong>
                            67%
                        </strong>

                    </div>

                    <div class="workload-track">

                        <div
                            class="workload-fill"
                            style="width:67%"
                        ></div>

                    </div>

                </div>


                <div class="hero-meta">

                    <div>
                        <span>COMPLETED</span>
                        <strong>04</strong>
                    </div>

                    <div>
                        <span>REMAINING</span>
                        <strong>02</strong>
                    </div>

                    <div>
                        <span>ON TIME</span>
                        <strong>100%</strong>
                    </div>

                </div>

            </div>



            <!-- NEXT STOP -->

<?php

$nextShipment = null;

foreach ($courierShipments as $shipment) {

    if (($shipment['status'] ?? '') === 'belum_dikirim') {
        $nextShipment = $shipment;
        break;
    }

}

?>

<div
    class="next-stop-card"
    data-reveal
>

    <div class="next-stop-label">

        <span class="small-label">
            NEXT STOP
        </span>

        <span class="next-stop-time">
            —
        </span>

    </div>


    <div class="stop-icon">
        →
    </div>


    <?php if ($nextShipment): ?>

        <h3>
            <?= htmlspecialchars(
                'PKF-' . str_pad(
                    $nextShipment['id_barang'],
                    4,
                    '0',
                    STR_PAD_LEFT
                )
            ) ?>
        </h3>


        <p>
            <?= htmlspecialchars(
                $nextShipment['alamat_tujuan']
            ) ?>
        </p>


        <div class="stop-route">

            <span>
                <?= htmlspecialchars(
                    $nextShipment['alamat_asal']
                ) ?>
            </span>

            <div class="mini-route">
                <i></i>
                <i></i>
                <i></i>
            </div>

            <span>
                <?= htmlspecialchars(
                    $nextShipment['alamat_tujuan']
                ) ?>
            </span>

        </div>


        <button
            type="button"
            class="route-button"
        >
            View delivery
            <span>→</span>
        </button>

    <?php else: ?>

        <h3>
            No pending shipment
        </h3>

        <p>
            There are no shipments waiting for pickup.
        </p>

    <?php endif; ?>

</div>

        </section>

        <!-- =================================================
             QUICK STATS
        ================================================== -->

        <section class="stats-grid courier-stats">


            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    TODAY'S PICKUPS
                </span>

                <strong>
                    06
                </strong>

                <small>
                    Scheduled for today
                </small>

                <div class="stat-indicator">
                    <i></i>
                    <span>2 remaining</span>
                </div>

            </div>


            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    DELIVERIES
                </span>

                <strong>
                    04
                </strong>

                <small>
                    Packages on route
                </small>

                <div class="stat-indicator">
                    <i></i>
                    <span>Active route</span>
                </div>

            </div>


            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    COMPLETED
                </span>

                <strong>
                    02
                </strong>

                <small>
                    Successfully delivered
                </small>

                <div class="stat-indicator completed-indicator">
                    <i></i>
                    <span>On schedule</span>
                </div>

            </div>


        </section>



        <!-- =================================================
             TODAY'S ROUTE
        ================================================== -->

        <section
            class="panel courier-route-panel"
            id="tasks"
            data-reveal
        >

            <div class="panel-heading">

                <div>

                    <span class="small-label">
                        TODAY
                    </span>

                    <h2>
                        Delivery route
                    </h2>

                </div>

                <span class="status">
                    4 active
                </span>

            </div>


            <div class="courier-route-list" id="courierShipmentList">

<?php if (empty($courierShipments)): ?>

    <div class="empty-state">

        <span>○</span>

        <strong>
            No shipments yet
        </strong>

        <p>
            Customer shipments will appear here.
        </p>

    </div>

<?php else: ?>

    <?php foreach ($courierShipments as $index => $shipment): ?>

        <?php

        $status = $shipment['status'] ?? 'belum_dikirim';

        if ($status === 'belum_dikirim') {

            $statusLabel = 'Pickup';
            $statusClass = '';

        } elseif ($status === 'sedang_dikirim') {

            $statusLabel = 'In transit';
            $statusClass = '';

        } elseif ($status === 'sudah_sampai') {

            $statusLabel = 'Delivered';
            $statusClass = 'delivered';

        } else {

            $statusLabel = ucfirst(
                str_replace('_', ' ', $status)
            );

            $statusClass = '';

        }

        ?>

        <div
            class="courier-route-item
            <?= $status === 'sudah_sampai' ? 'completed' : '' ?>
            <?= $status === 'sedang_dikirim' ? 'current' : '' ?>"
        >

            <div class="route-index">

                <span>
                    <?= str_pad(
                        $index + 1,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ) ?>
                </span>

            </div>


            <div class="route-main">

                <div class="route-title">

                    <strong>
                        <?= htmlspecialchars(
                            'PKF-' . str_pad(
                                $shipment['id_barang'],
                                4,
                                '0',
                                STR_PAD_LEFT
                            )
                        ) ?>
                    </strong>

                    <span
                        class="status <?= $statusClass ?>"
                    >
                        <?= htmlspecialchars(
                            $statusLabel
                        ) ?>
                    </span>

                </div>


                <span class="route-location">

                    <?= htmlspecialchars(
                        $shipment['alamat_asal']
                    ) ?>

                    →

                    <?= htmlspecialchars(
                        $shipment['alamat_tujuan']
                    ) ?>

                </span>

            </div>


            <div class="route-time">

                <strong>
                    <?= htmlspecialchars(
                        $shipment['nama_penerima']
                    ) ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $shipment['nama_pengirim']
                    ) ?>
                </span>

            </div>


            <div class="route-action">

                <?php if ($status === 'belum_dikirim'): ?>

                    <form method="post" action="courier-dashboard.php" style="margin:0;">
                        <input type="hidden" name="action" value="update_shipment_status">
                        <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id_barang'] ?>">
                        <input type="hidden" name="status" value="sedang_dikirim">
                        <button type="submit" class="route-button dark">
                            Ambil Paket
                        </button>
                    </form>

                <?php elseif ($status === 'sedang_dikirim' && (int) $shipment['id_kurir'] === $courierId): ?>

                    <form method="post" action="courier-dashboard.php" style="margin:0;">
                        <input type="hidden" name="action" value="update_shipment_status">
                        <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id_barang'] ?>">
                        <input type="hidden" name="status" value="sudah_sampai">
                        <button type="submit" class="route-button">
                            Tandai Selesai
                        </button>
                    </form>

                <?php endif; ?>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

        </section>
<!-- =================================================
     SHIPMENT DETAIL MODAL
================================================= -->

<div
    class="shipment-modal"
    id="shipmentModal"
    aria-hidden="true"
>

    <div class="shipment-modal-backdrop"></div>

    <div
        class="shipment-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalShipmentId"
    >

        <!-- MODAL HEADER -->

        <div class="shipment-modal-header">

            <div>

                <span class="small-label">
                    SHIPMENT DETAIL
                </span>

                <h2 id="modalShipmentId">
                    PKF-2847-01
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                id="modalClose"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <!-- STATUS -->

        <div class="modal-status-row">

            <div>

                <span class="modal-muted">
                    CURRENT STATUS
                </span>

                <strong id="modalStatus">
                    Pickup scheduled
                </strong>

            </div>

            <span
                class="status"
                id="modalStatusBadge"
            >
                Pickup
            </span>

        </div>


        <!-- ROUTE -->

        <div class="modal-route">

            <div class="modal-location">

                <span>
                    FROM
                </span>

                <strong id="modalFrom">
                    Jakarta
                </strong>

                <small>
                    Pickup location
                </small>

            </div>


            <div class="modal-route-line">

                <div class="modal-route-dot"></div>

                <div class="modal-route-progress"></div>

                <div class="modal-route-arrow">
                    →
                </div>

            </div>


            <div class="modal-location destination">

                <span>
                    TO
                </span>

                <strong id="modalTo">
                    Bandung
                </strong>

                <small>
                    Delivery destination
                </small>

            </div>

        </div>


        <!-- INFORMATION -->

        <div class="modal-info-grid">

            <div>

                <span>
                    SCHEDULE
                </span>

                <strong id="modalSchedule">
                    09:30
                </strong>

            </div>


            <div>

                <span>
                    PACKAGE
                </span>

                <strong>
                    Standard
                </strong>

            </div>


            <div>

                <span>
                    RECIPIENT
                </span>

                <strong id="modalRecipient">
                    Customer
                </strong>

            </div>


            <div>

                <span>
                    EST. ARRIVAL
                </span>

                <strong id="modalArrival">
                    Today
                </strong>

            </div>

        </div>


        <!-- TIMELINE -->

        <div class="modal-timeline">

            <div class="modal-timeline-item completed">

                <div class="modal-timeline-dot"></div>

                <div>

                    <strong>
                        Shipment created
                    </strong>

                    <span>
                        Order has been registered
                    </span>

                </div>

            </div>


            <div
                class="modal-timeline-item"
                id="timelinePickup"
            >

                <div class="modal-timeline-dot"></div>

                <div>

                    <strong>
                        Pickup
                    </strong>

                    <span>
                        Courier pickup pending
                    </span>

                </div>

            </div>


            <div
                class="modal-timeline-item"
                id="timelineTransit"
            >

                <div class="modal-timeline-dot"></div>

                <div>

                    <strong>
                        In transit
                    </strong>

                    <span>
                        Package is on the way
                    </span>

                </div>

            </div>


            <div
                class="modal-timeline-item"
                id="timelineDelivered"
            >

                <div class="modal-timeline-dot"></div>

                <div>

                    <strong>
                        Delivered
                    </strong>

                    <span>
                        Package successfully delivered
                    </span>

                </div>

            </div>

        </div>


        <!-- ACTION -->

        <div class="modal-action">

            <button
                type="button"
                class="modal-action-button"
                id="shipmentAction"
            >
                Start pickup
                <span>→</span>
            </button>

        </div>

    </div>

</div>


        <!-- =================================================
             DELIVERY INFORMATION
        ================================================== -->

        <section
            class="content-grid courier-info-grid"
            id="deliveries"
        >


            <!-- ROUTE STATUS -->

            <div
                class="panel route-status-panel"
                data-reveal
            >

                <div class="panel-heading">

                    <div>

                        <span class="small-label">
                            ROUTE STATUS
                        </span>

                        <h2>
                            On schedule
                        </h2>

                    </div>

                    <div class="route-status-icon">
                        ✓
                    </div>

                </div>


                <p>
                    Your current route is running normally.
                    Keep your location updated during delivery.
                </p>


                <div class="route-status-line">

                    <div class="status-pulse">
                        <i></i>
                    </div>

                    <div>

                        <strong>
                            Route is active
                        </strong>

                        <span>
                            Last updated just now
                        </span>

                    </div>

                </div>

            </div>



            <!-- NEXT DELIVERY -->

            <div
                class="panel delivery-focus"
                data-reveal
            >

                <span class="small-label">
                    NEXT DELIVERY
                </span>


                <div class="delivery-focus-header">

                    <h2>
                        PKF-2846-22
                    </h2>

                    <span class="status">
                        In transit
                    </span>

                </div>


                <div class="delivery-address">

                    <span>
                        DELIVERY TO
                    </span>

                    <strong>
                        Cimahi, Jawa Barat
                    </strong>

                </div>


                <div class="delivery-actions">

                    <button
                        type="button"
                        class="route-button dark"
                    >
                        View details
                        <span>→</span>
                    </button>

                    <button
                        type="button"
                        class="icon-button"
                        title="Open route"
                    >
                        ↗
                    </button>

                </div>

            </div>


        </section>



        <!-- =================================================
             PERFORMANCE
        ================================================== -->

        <section
            class="panel courier-performance"
            id="history"
            data-reveal
        >

            <div class="panel-heading">

                <div>

                    <span class="small-label">
                        PERFORMANCE
                    </span>

                    <h2>
                        Today's progress
                    </h2>

                </div>

                <span class="status delivered">
                    On track
                </span>

            </div>


            <div class="performance-grid">


                <div class="performance-item">

                    <div class="performance-head">

                        <span>
                            Pickup completion
                        </span>

                        <strong>
                            67%
                        </strong>

                    </div>

                    <div class="performance-track">

                        <div
                            class="performance-fill"
                            style="width:67%"
                        ></div>

                    </div>

                    <small>
                        4 of 6 scheduled pickups
                    </small>

                </div>



                <div class="performance-item">

                    <div class="performance-head">

                        <span>
                            Delivery completion
                        </span>

                        <strong>
                            50%
                        </strong>

                    </div>

                    <div class="performance-track">

                        <div
                            class="performance-fill"
                            style="width:50%"
                        ></div>

                    </div>

                    <small>
                        2 of 4 active deliveries
                    </small>

                </div>



                <div class="performance-item">

                    <div class="performance-head">

                        <span>
                            Route efficiency
                        </span>

                        <strong>
                            94%
                        </strong>

                    </div>

                    <div class="performance-track">

                        <div
                            class="performance-fill"
                            style="width:94%"
                        ></div>

                    </div>

                    <small>
                        Running according to schedule
                    </small>

                </div>


            </div>

        </section>



        <!-- =================================================
             QUICK ACTION
        ================================================== -->

        <section
            class="quick-action"
            id="settings"
            data-reveal
        >

            <div>

                <span class="small-label">
                    COURIER STATUS
                </span>

                <h2>
                    You're ready for the next stop.
                </h2>

                <p>
                    Keep your delivery status updated
                    so customers can follow their packages.
                </p>

            </div>


            <a
                href="logout.php"
                class="btn-primary"
            >
                End session
                <span>→</span>
            </a>

        </section>


    </main>

</div>


<script src="assets/js/app.js"></script>

</body>

</html>