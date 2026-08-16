# Packify - Aplikasi Pengiriman Paket

## Deskripsi Singkat Aplikasi

Packify (Packed Delivery) adalah sebuah aplikasi web yang mengelola layanan jasa pengiriman paket. Aplikasi ini mendukung dua _role_ pengguna utama, yaitu **Pelanggan** (untuk memesan dan mengelola pengiriman) serta **Kurir** (untuk mengambil dan mengantarkan paket).

Proyek ini dibangun secara khusus dengan pendekatan _vulnerable by design_ (sengaja ditanamkan celah keamanan) untuk memenuhi kebutuhan _Assessment_ KSM Cyber Security sekaligus sebagai portofolio pengembangan web dan infrastruktur.

---

## Teknologi yang Digunakan

- **Backend:** PHP 8.2 Native (Procedural / `mysqli`)
- **Frontend:** HTML5, Custom CSS, Bootstrap 5.3
- **Database:** MySQL 8.0
- **Infrastruktur:** Docker & Docker Compose

---

## Cara Menjalankan Aplikasi

1. Pastikan **Docker** dan **Docker Desktop** sudah terinstal dan beroperasi (status _Engine running_) di komputer Anda.
2. Buka Terminal atau PowerShell.
3. Arahkan direktori aktif ke dalam folder proyek utama ini.
4. Jalankan perintah berikut untuk membangun dan menyalakan _container_:
   `docker compose up -d --build`
5. Buka _browser_ dan akses aplikasi melalui tautan: `http://localhost:8000`
6. Untuk mematikan server, gunakan perintah: `docker compose down`

---

## Struktur Folder

Berikut adalah hierarki direktori dan _file_ dalam proyek ini:

```text
packed-delivery/
├── asset/                 # Berisi gambar dan aset visual (logo, ilustrasi)
├── service/
│   └── database.php       # Konfigurasi koneksi ke database MySQL
├── sql/
│   └── 01_users.sql       # Skema tabel users
├── partials/
│   └── navbar.php         # Navbar bersama (dashboard, profile, edit)
├── Dockerfile             # Konfigurasi environment image PHP-Apache
├── docker-compose.yml     # Konfigurasi layanan container (Web App & Database)
├── README.md                 # Dokumentasi proyek (file ini)
├── functions.php            # Helper session, CSRF, flash message, validasi form
├── index.php              # Landing page utama
├── login.php              # Halaman autentikasi (Login)
├── register.php           # Halaman pendaftaran akun baru
├── dashboard.php          # Halaman beranda setelah login
├── barang.php             # Form pemesanan pengiriman paket
├── delivery.php           # Halaman informasi pengiriman untuk kurir
├── package.php            # Halaman informasi paket
├── vehicle.php            # Halaman informasi kendaraan kurir
├── worker.php             # Halaman informasi karyawan/kurir
├── sender.php             # Halaman informasi pengirim
├── price.php              # Halaman informasi tarif pengiriman
├── profile.php            # Halaman profil pengguna
├── edit.php               # Form pembaruan data akun & password
├── delete.php             # Skrip untuk menghapus akun
├── style.css              # Styling CSS global
├── Box.css                # Styling CSS untuk komponen box/card
├── Lstyle.css             # Styling CSS untuk form login
└── barang.css             # Styling CSS untuk form barang
```

## Port yang Digunakan

| Service              | Port Host | Port Container | Keterangan                                             |
| :------------------- | :-------- | :------------- | :----------------------------------------------------- |
| **Web App (PHP)**    | `8000`    | `80`           | Antarmuka aplikasi Packify                             |
| **Database (MySQL)** | `3306`    | `3306`         | Sengaja diekspos ke host untuk simulasi celah jaringan |

---

## Informasi Database

Koneksi database pada aplikasi (misalnya di `service/database.php`) wajib menggunakan parameter berikut agar dapat berkomunikasi antar-_container_:

- **Host:** `db`
- **Nama Database:** `packify`
- **Username:** `packifyroot`
- **Password:** `Pass123`
- **Root Password (Opsional/Admin):** `root`

---

## Kredensial Default (Testing)

Aplikasi ini tidak menyertakan injeksi data otomatis (_database seeding_) pada saat pertama kali berjalan. Untuk melakukan _testing_ awal:

1. Akses halaman utama dan klik tombol **Sign Up** (`register.php`).
2. Daftarkan dua akun baru dengan _role_ yang berbeda (satu sebagai `pelanggan` dan satu sebagai `kurir`).
3. Gunakan _username_ dan _password_ yang baru Anda daftarkan tersebut untuk **Log In** dan mengeksplorasi _dashboard_ masing-masing _role_.

---

## Informasi Lain (Skenario Kerentanan / Assessment)

Environment aplikasi ini dikonfigurasikan untuk memfasilitasi Worksheet Assessment Security. Terdapat lima titik kerentanan (_vulnerability_) yang ditanamkan:

1.  **IDOR (Application):** Modifikasi parameter URL (seperti `?id=`) pada aksi ubah/hapus paket pelanggan tidak divalidasi kepemilikannya.
2.  **Broken Authentication (Application):** Form penggantian kata sandi mengabaikan atau tidak memvalidasi kata sandi lama.
3.  **Root Privileged Container (Infrastructure):** Container PHP-Apache di dalam `Dockerfile` berjalan sebagai `root` secara _default_.
4.  **Vulnerable Dependencies (Infrastructure):** Menggunakan dependensi versi lawas yang memiliki riwayat _Common Vulnerabilities and Exposures_ (CVE) publik.
5.  **Exposed Port (Network):** Port database `3306` dipetakan langsung ke host, membuka celah akses _direct_ maupun _brute-force_ ke MySQL.
