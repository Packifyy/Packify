<?php
session_start();

/* =====================================================
   CUSTOMER ACCESS PROTECTION
===================================================== */

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'customer'
) {
    header('Location: login.php?role=customer');
    exit;
}

$name = $_SESSION['identity'] ?? 'Customer';

$firstName = explode(' ', trim($name))[0];

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
        content="Packify customer dashboard"
    >

    <title>Dashboard — Packify</title>

    <link
        rel="stylesheet"
        href="assets/css/dashboard.css"
    >

</head>


<body>

<div class="dashboard">


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
            CUSTOMER
        </div>


        <nav>

            <a
                class="active"
                href="#overview"
            >
                <span>01</span>
                Overview
            </a>

            <a href="#shipments">
                <span>02</span>
                My shipments
            </a>

            <a href="#tracking">
                <span>03</span>
                Track package
            </a>

            <a href="#history">
                <span>04</span>
                History
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


        <!-- HEADER -->

        <header class="dashboard-header">

            <div class="dashboard-heading">

                <div class="small-label">
                    CUSTOMER DASHBOARD
                </div>

                <h1 id="overview">
                    Good morning,
                    <?= htmlspecialchars($firstName) ?>.
                </h1>

                <p>
                    Here's what's happening with your shipments.
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
                        Customer
                    </span>

                </div>

            </div>

        </header>



        <!-- =================================================
             QUICK STATS
        ================================================== -->

        <section class="stats-grid">

            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    ACTIVE SHIPMENTS
                </span>

                <strong>
                    03
                </strong>

                <small>
                    Currently in progress
                </small>

            </div>


            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    DELIVERED
                </span>

                <strong>
                    18
                </strong>

                <small>
                    Successfully delivered
                </small>

            </div>


            <div
                class="stat-card"
                data-reveal
            >

                <span>
                    IN TRANSIT
                </span>

                <strong>
                    02
                </strong>

                <small>
                    Currently on the way
                </small>

            </div>

        </section>



        <!-- =================================================
             SHIPMENT OVERVIEW + TRACKING
        ================================================== -->

        <section class="content-grid">

        <section
    class="panel"
    data-reveal
>

    <div class="panel-heading">

        <div>

            <span class="small-label">
                MY SHIPMENTS
            </span>

            <h2>
                Your packages
            </h2>

        </div>

        <button
            type="button"
            class="form-button primary"
            onclick="openModal(document.getElementById('shipmentFormModal'))"
        >
            + New shipment
        </button>

    </div>

    <div
        class="shipment-list"
        id="customerShipmentList"
    ></div>

</section>

            <!-- RECENT SHIPMENT -->

            <div
                class="panel large-panel"
                id="shipments"
                data-reveal
            >

                <div class="panel-heading">

                    <div>

                        <span class="small-label">
                            RECENT SHIPMENT
                        </span>

                        <h2>
                            PKF-2847-01
                        </h2>

                    </div>

                    <span class="status">
                        In transit
                    </span>

                </div>


                <!-- ROUTE -->

                <div class="shipment-route">

                    <div class="route-location">

                        <span>
                            FROM
                        </span>

                        <strong>
                            Jakarta
                        </strong>

                    </div>


                    <div class="route-line">

                        <div class="route-progress"></div>

                        <div class="route-truck">
                            →
                        </div>

                    </div>


                    <div class="route-location destination">

                        <span>
                            TO
                        </span>

                        <strong>
                            Bandung
                        </strong>

                    </div>

                </div>


                <!-- PROGRESS -->

                <div class="shipment-progress">

                    <div class="progress-heading">

                        <span>
                            Shipment progress
                        </span>

                        <strong>
                            72%
                        </strong>

                    </div>

                    <div class="progress-track">

                        <div
                            class="progress-fill"
                            style="width:72%"
                        ></div>

                    </div>

                    <div class="progress-meta">

                        <span>
                            Picked up
                        </span>

                        <span>
                            Estimated arrival · Tomorrow
                        </span>

                    </div>

                </div>


                <!-- TIMELINE -->

                <div class="tracking-line">


                    <div class="tracking-item completed">

                        <div class="dot"></div>

                        <div>

                            <strong>
                                Package picked up
                            </strong>

                            <span>
                                Jakarta · 09:42
                            </span>

                        </div>

                    </div>


                    <div class="tracking-item completed">

                        <div class="dot"></div>

                        <div>

                            <strong>
                                Distribution center
                            </strong>

                            <span>
                                Package is being processed
                            </span>

                        </div>

                    </div>


                    <div class="tracking-item current">

                        <div class="dot"></div>

                        <div>

                            <strong>
                                On the way
                            </strong>

                            <span>
                                Currently travelling to Bandung
                            </span>

                        </div>

                    </div>


                    <div class="tracking-item">

                        <div class="dot"></div>

                        <div>

                            <strong>
                                Delivery
                            </strong>

                            <span>
                                Awaiting arrival
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            <!-- TRACK PACKAGE -->

            <div
                class="panel tracking-panel"
                id="tracking"
                data-reveal
            >

                <span class="small-label">
                    TRACK PACKAGE
                </span>

                <h2>
                    Where is your package?
                </h2>

                <p>
                    Enter your tracking number to see
                    the latest shipment status.
                </p>


                <form
                    id="trackingForm"
                    class="tracking-form"
                >

                    <input
                        type="text"
                        name="tracking_number"
                        placeholder="PKF-XXXX-XX"
                        autocomplete="off"
                        maxlength="30"
                        required
                    >

                    <button type="submit">
                        Track
                        <span>→</span>
                    </button>

                </form>


                <div
                    id="trackingResult"
                    class="tracking-result"
                ></div>


                <!-- SMALL INFO -->

                <div class="tracking-tip">

                    <span class="tip-icon">
                        i
                    </span>

                    <div>

                        <strong>
                            Tracking tip
                        </strong>

                        <p>
                            Your tracking number can be found
                            on your shipment receipt.
                        </p>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             HISTORY
        ================================================== -->

        <section
            class="panel activity"
            id="history"
            data-reveal
        >

            <div class="panel-heading">

                <div>

                    <span class="small-label">
                        ACTIVITY
                    </span>

                    <h2>
                        Shipment history
                    </h2>

                </div>

                <a href="#">
                    View all →
                </a>

            </div>


            <div class="history-row">

                <div class="history-info">

                    <strong>
                        PKF-2847-01
                    </strong>

                    <span>
                        Jakarta → Bandung
                    </span>

                </div>

                <span class="status delivered">
                    Delivered
                </span>

                <span class="history-date">
                    14 Aug 2026
                </span>

            </div>


            <div class="history-row">

                <div class="history-info">

                    <strong>
                        PKF-2831-09
                    </strong>

                    <span>
                        Depok → Jakarta
                    </span>

                </div>

                <span class="status delivered">
                    Delivered
                </span>

                <span class="history-date">
                    08 Aug 2026
                </span>

            </div>


            <div class="history-row">

                <div class="history-info">

                    <strong>
                        PKF-2798-04
                    </strong>

                    <span>
                        Bogor → Bekasi
                    </span>

                </div>

                <span class="status">
                    In transit
                </span>

                <span class="history-date">
                    05 Aug 2026
                </span>

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
                    NEED SOMETHING ELSE?
                </span>

                <h2>
                    Ready to send a package?
                </h2>

                <p>
                    Start a new shipment and let Packify
                    handle the journey.
                </p>

            </div>


            <a
                href="portal.php"
                class="btn-primary"
            >
                Create shipment
                <span>→</span>
            </a>

        </section>


    </main>

</div>

<!-- =================================================
     PROFILE MODAL
================================================= -->

<div
    class="packify-modal"
    id="profileModal"
    aria-hidden="true"
>

    <div class="packify-modal-backdrop"></div>

    <div class="packify-modal-card">

        <div class="packify-modal-header">

            <div>

                <span class="small-label">
                    ACCOUNT
                </span>

                <h2>
                    Edit profile
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-modal
            >
                ×
            </button>

        </div>


        <form
            class="packify-form"
            id="profileForm"
        >

            <div class="form-field">

                <label>
                    FULL NAME
                </label>

                <input
                    type="text"
                    name="name"
                    required
                >

            </div>


            <div class="form-field">

                <label>
                    PHONE
                </label>

                <input
                    type="tel"
                    name="phone"
                    placeholder="08xxxxxxxxxx"
                >

            </div>


            <div class="form-field">

                <label>
                    ADDRESS
                </label>

                <textarea
                    name="address"
                    placeholder="Your address"
                ></textarea>

            </div>


            <div class="form-actions">

                <button
                    type="button"
                    class="form-button secondary"
                    data-close-modal
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="form-button primary"
                >
                    Save changes
                </button>

            </div>

        </form>

    </div>

</div>



<!-- =================================================
     PASSWORD MODAL
================================================= -->

<div
    class="packify-modal"
    id="passwordModal"
    aria-hidden="true"
>

    <div class="packify-modal-backdrop"></div>

    <div class="packify-modal-card">

        <div class="packify-modal-header">

            <div>

                <span class="small-label">
                    SECURITY
                </span>

                <h2>
                    Change password
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-modal
            >
                ×
            </button>

        </div>


        <form
            class="packify-form"
            id="passwordForm"
        >

            <div class="form-field">

                <label>
                    CURRENT PASSWORD
                </label>

                <input
                    type="password"
                    name="old_password"
                    required
                >

            </div>


            <div class="form-field">

                <label>
                    NEW PASSWORD
                </label>

                <input
                    type="password"
                    name="new_password"
                    minlength="6"
                    required
                >

            </div>


            <div class="form-field">

                <label>
                    CONFIRM NEW PASSWORD
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    minlength="6"
                    required
                >

            </div>


            <div class="form-actions">

                <button
                    type="button"
                    class="form-button secondary"
                    data-close-modal
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="form-button primary"
                >
                    Update password
                </button>

            </div>

        </form>

    </div>

</div>



<!-- =================================================
     ADD / EDIT SHIPMENT
================================================= -->

<div
    class="packify-modal"
    id="shipmentFormModal"
    aria-hidden="true"
>

    <div class="packify-modal-backdrop"></div>

    <div class="packify-modal-card">

        <div class="packify-modal-header">

            <div>

                <span class="small-label">
                    SHIPMENT
                </span>

                <h2>
                    Create shipment
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-modal
            >
                ×
            </button>

        </div>


        <form
            class="packify-form"
            id="shipmentForm"
        >

            <div class="form-field">

                <label>
                    PACKAGE DESCRIPTION
                </label>

                <input
                    type="text"
                    name="description"
                    placeholder="e.g. Documents"
                    required
                >

            </div>


            <div class="form-field">

                <label>
                    RECIPIENT
                </label>

                <input
                    type="text"
                    name="recipient"
                    placeholder="Recipient name"
                    required
                >

            </div>


            <div class="form-field">

                <label>
                    DELIVERY ADDRESS
                </label>

                <textarea
                    name="address"
                    placeholder="Complete delivery address"
                    required
                ></textarea>

            </div>


            <div class="form-field">

                <label>
                    QUANTITY
                </label>

                <input
                    type="number"
                    name="quantity"
                    min="1"
                    value="1"
                    required
                >

            </div>


            <label class="form-check">

                <input
                    type="checkbox"
                    name="fragile"
                >

                Package is fragile

            </label>


            <div class="form-actions">

                <button
                    type="button"
                    class="form-button secondary"
                    data-close-modal
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="form-button primary"
                >
                    Save shipment
                </button>

            </div>

        </form>

    </div>

</div>



<!-- =================================================
     SHIPMENT DETAIL
================================================= -->

<div
    class="packify-modal"
    id="shipmentDetailModal"
    aria-hidden="true"
>

    <div class="packify-modal-backdrop"></div>

    <div class="packify-modal-card">

        <div class="packify-modal-header">

            <div>

                <span class="small-label">
                    SHIPMENT DETAIL
                </span>

                <h2 id="detailShipmentId">
                    PKF-2847-01
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-modal
            >
                ×
            </button>

        </div>


        <div class="detail-status">

            <span>
                CURRENT STATUS
            </span>

            <span
                class="status"
                id="detailStatus"
            >
                In transit
            </span>

        </div>


        <div class="detail-grid">

            <div class="detail-item">

                <span>
                    DESCRIPTION
                </span>

                <strong id="detailDescription">
                    -
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    RECIPIENT
                </span>

                <strong id="detailRecipient">
                    -
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    ADDRESS
                </span>

                <strong id="detailAddress">
                    -
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    QUANTITY
                </span>

                <strong id="detailQuantity">
                    -
                </strong>

            </div>

        </div>

    </div>

</div>

<script src="assets/js/app.js"></script>

</body>
</html>