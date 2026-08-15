<?php
// Konfigurasi koneksi ke MySQL via Docker
$host = "db"; 
$user = "packifyroot";
$pass = "Pass123";
$dbname = "packify";

// Membuat koneksi
$db = mysqli_connect($host, $user, $pass, $dbname);

// Cek koneksi
if (!$db) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>