<?php
session_start();

/*
|--------------------------------------------------------------------------
| PACKIFY PORTAL
|--------------------------------------------------------------------------
| Portal selalu menjadi halaman pemilihan akses.
| Jangan redirect user yang sudah login dari sini.
|
| Jika user ingin kembali ke dashboard, gunakan halaman dashboard
| atau login kembali setelah logout.
|--------------------------------------------------------------------------
*/
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
        content="Packify Portal — Choose your access"
    >

    <title>
        Portal — Packify
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="portal-page">


<main class="portal-container">


    <!-- =================================================
         BRAND / INTRO
    ================================================== -->

    <section class="portal-brand">


        <a
            href="index.php"
            class="portal-logo"
        >
            Pack<span>i</span>fy
        </a>


        <p class="portal-label">
            PACKIFY PORTAL
        </p>


        <h1>

            One platform.

            <br>

            <span>Two ways in.</span>

        </h1>


        <p class="portal-description">

            Pilih akses yang sesuai untuk mulai mengelola
            pengiriman atau menjalankan tugas delivery.

        </p>


        <a
            href="index.php"
            class="portal-back"
        >
            ← Back to homepage
        </a>


    </section>



    <!-- =================================================
         ACCESS OPTIONS
    ================================================== -->

    <section class="portal-options">


        <!-- =================================================
             CUSTOMER
        ================================================== -->

        <a
            href="login.php?role=customer"
            class="portal-card"
        >

            <div class="portal-card-top">

                <span class="portal-number">
                    01
                </span>


                <span class="portal-arrow">
                    ↗
                </span>

            </div>


            <div class="portal-card-content">

                <span class="portal-card-label">
                    CUSTOMER
                </span>


                <h2>
                    Pelanggan
                </h2>


                <p>

                    Buat pengiriman, lacak paket,
                    dan lihat seluruh aktivitas
                    pengirimanmu dalam satu tempat.

                </p>

            </div>


            <div class="portal-card-bottom">

                <span>
                    Masuk sebagai pelanggan
                </span>


                <span class="portal-card-arrow">
                    →
                </span>

            </div>

        </a>



        <!-- =================================================
             COURIER
        ================================================== -->

        <a
            href="login.php?role=courier"
            class="portal-card"
        >

            <div class="portal-card-top">

                <span class="portal-number">
                    02
                </span>


                <span class="portal-arrow">
                    ↗
                </span>

            </div>


            <div class="portal-card-content">

                <span class="portal-card-label">
                    COURIER
                </span>


                <h2>
                    Kurir
                </h2>


                <p>

                    Kelola pickup, pantau rute,
                    update status pengiriman,
                    dan selesaikan delivery secara terstruktur.

                </p>

            </div>


            <div class="portal-card-bottom">

                <span>
                    Masuk sebagai kurir
                </span>


                <span class="portal-card-arrow">
                    →
                </span>

            </div>

        </a>


    </section>



    <!-- =================================================
         FOOTER
    ================================================== -->

    <div class="portal-footer">

        <span>
            Secure access
        </span>


        <span class="portal-footer-dot">
            •
        </span>


        <span>
            Packify Logistics Platform
        </span>

    </div>


</main>



<script src="assets/js/app.js"></script>

</body>

</html>