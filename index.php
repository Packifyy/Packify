<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Packify — Shipping, simplified.</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<header class="navbar">

    <div class="nav-container">

        <a
            href="index.php"
            class="logo"
        >
            Packi<span>fy</span>
        </a>


        <nav class="nav-menu">

            <a href="#how-it-works">
                How it works
            </a>

            <a href="#features">
                Features
            </a>

            <a href="#tracking">
                Tracking
            </a>

        </nav>


        <div class="nav-actions">

            <a
                href="login.php"
                class="login-link"
            >
                Log in
            </a>

            <a
                href="portal.php"
                class="btn-primary"
            >
                Get started
            </a>

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<main>


    <!-- =================================================
         HERO
    ================================================== -->

    <section class="hero">

        <div class="hero-container">


            <!-- HERO LEFT -->

            <div class="hero-content reveal">

                <div class="eyebrow">

                    <span></span>

                    MODERN LOGISTICS PLATFORM

                </div>


                <h1>

                    Shipping,

                    <br>

                    <em>simplified.</em>

                </h1>


                <p>

                    Kirim paket tanpa ribet. Kelola pengiriman,
                    pantau perjalanan, dan tetap terhubung dalam
                    satu platform yang sederhana.

                </p>


                <div class="hero-buttons">

                    <a
                        href="portal.php"
                        class="btn-primary"
                    >

                        Start shipping

                        <span>→</span>

                    </a>


                    <a
                        href="#tracking"
                        class="btn btn-light btn-large"
                    >

                        Track a package

                    </a>

                </div>

            </div>



            <!-- HERO RIGHT -->

            <div class="shipment-card reveal">


                <div class="shipment-header">

                    <div>

                        <span class="card-label">
                            SHIPMENT
                        </span>

                        <strong>
                            PKF-2847-01
                        </strong>

                    </div>


                    <span class="status">
                        In transit
                    </span>

                </div>



                <div class="timeline">


                    <div class="timeline-item active">

                        <div class="timeline-dot"></div>


                        <div>

                            <strong>
                                Jakarta
                            </strong>

                            <p>
                                Package picked up · 09:42
                            </p>

                        </div>

                    </div>



                    <div class="timeline-item active">

                        <div class="timeline-dot"></div>


                        <div>

                            <strong>
                                Distribution Center
                            </strong>

                            <p>
                                Package is being processed
                            </p>

                        </div>

                    </div>



                    <div class="timeline-item">

                        <div class="timeline-dot"></div>


                        <div>

                            <strong>
                                Bandung
                            </strong>

                            <p>
                                Estimated arrival · Tomorrow
                            </p>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </section>



    <!-- =================================================
         FEATURES
    ================================================== -->

    <section
        class="features"
        id="features"
    >

        <div class="section-container">


            <div class="section-heading reveal">

                <div class="eyebrow">

                    <span></span>

                    BUILT FOR SIMPLICITY

                </div>


                <h2>

                    Everything your shipment

                    <br>

                    needs.

                </h2>


                <p>

                    Dari pickup sampai paket tiba, Packify membantu
                    membuat proses pengiriman lebih terorganisir.

                </p>

            </div>



            <div class="feature-grid">


                <article
                    class="feature-card"
                    data-reveal
                >

                    <span class="feature-number">
                        01
                    </span>


                    <h3>
                        Easy shipping
                    </h3>


                    <p>

                        Buat pengiriman baru hanya dalam beberapa
                        langkah sederhana.

                    </p>

                </article>



                <article
                    class="feature-card"
                    data-reveal
                >

                    <span class="feature-number">
                        02
                    </span>


                    <h3>
                        Real-time tracking
                    </h3>


                    <p>

                        Pantau perjalanan paket dan dapatkan informasi
                        status pengiriman.

                    </p>

                </article>



                <article
                    class="feature-card"
                    data-reveal
                >

                    <span class="feature-number">
                        03
                    </span>


                    <h3>
                        Courier network
                    </h3>


                    <p>

                        Hubungkan pelanggan dengan kurir untuk proses
                        pickup dan delivery.

                    </p>

                </article>


            </div>

        </div>

    </section>



    <!-- =================================================
         HOW IT WORKS
    ================================================== -->

    <section
        class="how-it-works"
        id="how-it-works"
    >

        <div class="section-container">


            <div class="section-heading reveal">

                <div class="eyebrow">

                    <span></span>

                    HOW IT WORKS

                </div>


                <h2>

                    From your door

                    <br>

                    to theirs.

                </h2>


                <p>

                    Proses pengiriman dirancang supaya mudah dipahami
                    baik untuk pelanggan maupun kurir.

                </p>

            </div>



            <div class="steps">


                <div
                    class="step"
                    data-reveal
                >

                    <span>
                        01
                    </span>


                    <h3>
                        Create shipment
                    </h3>


                    <p>

                        Masukkan detail paket dan tujuan pengiriman.

                    </p>

                </div>



                <div
                    class="step"
                    data-reveal
                >

                    <span>
                        02
                    </span>


                    <h3>
                        Courier pickup
                    </h3>


                    <p>

                        Kurir menerima permintaan dan mengambil paket.

                    </p>

                </div>



                <div
                    class="step"
                    data-reveal
                >

                    <span>
                        03
                    </span>


                    <h3>
                        Track & deliver
                    </h3>


                    <p>

                        Pantau perjalanan paket sampai diterima.

                    </p>

                </div>


            </div>

        </div>

    </section>



    <!-- =================================================
         TRACKING
    ================================================== -->

    <section
        class="tracking"
        id="tracking"
    >

        <div class="tracking-container">


            <div
                class="tracking-copy"
                data-reveal
            >

                <div class="eyebrow">

                    <span></span>

                    PACKAGE TRACKING

                </div>


                <h2>

                    Know where your

                    <br>

                    package is.

                </h2>


                <p>

                    Masukkan nomor resi untuk melihat status
                    perjalanan paket secara real-time.

                </p>

            </div>



            <!-- TRACKING FORM -->

            <div
                class="tracking-wrapper"
                data-reveal
            >


                <form
                    id="trackingForm"
                    class="tracking-form"
                >

                    <input
                        type="text"
                        name="tracking_number"
                        placeholder="Enter tracking number"
                        autocomplete="off"
                        maxlength="30"
                        required
                    >


                    <button type="submit">

                        <span>
                            Track package
                        </span>

                        <span>
                            →
                        </span>

                    </button>

                </form>



                <!--
                    HASIL TRACKING AKAN MUNCUL DI SINI
                    MELALUI app.js
                -->

                <div
                    id="trackingResult"
                    class="tracking-result"
                ></div>


                <div class="tracking-hint">

                    <span>●</span>

                    Example:
                    <strong>PKF-2847-01</strong>

                </div>


            </div>

        </div>

    </section>


</main>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="footer-container">


        <div class="logo">

            Packi<span>fy</span>

        </div>


        <p>

            © <span data-year>2026</span>
            Packify. Built for better delivery.

        </p>


    </div>

</footer>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="assets/js/app.js"></script>


</body>

</html>