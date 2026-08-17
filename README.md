# Packify — Aplikasi Layanan Pengiriman Paket

---

## a. Deskripsi Singkat Aplikasi

**Packify** (_Packed Delivery_) adalah aplikasi berbasis web yang menyediakan platform pengelolaan layanan pengiriman dan ekspedisi paket secara _end-to-end_. Aplikasi ini dirancang dengan antarmuka modern dan mendukung alur operasional antara dua _role_ pengguna utama:

1. **Pelanggan (_Customer_):**
   - Membuat pesanan pengiriman paket baru (_New Shipment_).
   - Melihat daftar dan riwayat pengiriman (_My Shipments & Activity History_).
   - Melacak lokasi dan status paket secara _real-time_ (_Track Package_).
   - Mengedit data pesanan atau membatalkan pengiriman paket yang belum diproses.
   - Mengelola profil dan kata sandi akun (_Settings & Profile_).

2. **Kurir (_Courier_):**
   - Melihat daftar paket aktif yang tersedia untuk diambil (_Available Pickups_).
   - Mengklaim atau mengambil paket untuk dikirimkan (_Pickup Package_).
   - Memperbarui status perjalanan paket dari _Belum Dikirim_ $\rightarrow$ _Sedang Dikirim_ $\rightarrow$ _Sudah Sampai_ (_Complete Delivery_).
   - Memantau riwayat pengiriman yang telah diselesaikan.

> **Catatan Laboratorium / Assessment:**  
> Repositori ini dikonfigurasikan secara khusus dengan pendekatan **_Vulnerable-by-Design_** (mengandung 5 titik kerentanan yang sengaja ditanamkan pada level Aplikasi, Komponen, Infrastruktur, dan Jaringan) untuk memenuhi kebutuhan _Worksheet Assessment Cyber Security_ dan portofolio keamanan siber.

---

## b. Teknologi yang Digunakan

- **Backend & Server-Side:** PHP 8.2 Native (Procedural, ekstensi `mysqli` dan `pdo_mysql`)
- **Web Server:** Apache 2.4 (dengan modul `mod_rewrite` aktif)
- **Database:** MySQL 8.0 (Relational Database Management System)
- **Frontend:** HTML5, CSS3 kustom murni (Custom Design System & Responsif), Bootstrap 5.3, serta dependensi pustaka JavaScript pihak ketiga (jQuery 3.4.1)
- **Containerization & Orchestration:** Docker Engine & Docker Compose

---

## c. Cara Menjalankan Aplikasi

Pastikan sistem operasi Anda telah terpasang **Docker** dan **Docker Desktop** (status: _Engine Running_).

### 1. Clone atau Buka Folder Proyek

Buka Terminal (Linux/macOS) atau PowerShell (Windows), lalu arahkan ke folder proyek:

```bash
git clone <URL_REPOSITORY>
cd Packify
```

### 2. Build dan Jalankan Container

Jalankan perintah berikut untuk mengunduh image, membangun container, dan menginisialisasi database secara otomatis di latar belakang (_detached mode_):

```bash
docker compose up -d --build
```

### 3. Akses Aplikasi di Browser

Setelah container berstatus `healthy` / `Up`, buka peramban web dan akses tautan:

- **Landing Page:** [http://localhost:8000](http://localhost:8000)
- **Halaman Login:** [http://localhost:8000/login.php](http://localhost:8000/login.php)
- **Halaman Register:** [http://localhost:8000/register.php](http://localhost:8000/register.php)

### 4. Menghentikan Server

Jika proses pengujian telah selesai, hentikan container dengan perintah:

```bash
docker compose down
```

_(Tambahkan flag `-v` jika ingin membersihkan volume data database: `docker compose down -v`)_.

---

## d. Port yang Digunakan

Aplikasi ini menggunakan konfigurasi port berikut pada `docker-compose.yml`:

| Layanan (_Service_) | Container Name | Port Host (Luar) | Port Container (Dalam) |  Protokol   | Deskripsi & Fungsi                                                                          |
| :------------------ | :------------- | :--------------: | :--------------------: | :---------: | :------------------------------------------------------------------------------------------ |
| **Web Application** | `packify_app`  |      `8000`      |          `80`          | TCP / HTTP  | Antarmuka web utama Packify yang diakses melalui browser (`http://localhost:8000`).         |
| **Database Server** | `packify_db`   |      `3306`      |         `3306`         | TCP / MySQL | Port database MySQL (sengaja dipetakan terbuka ke host untuk simulasi kerentanan jaringan). |

---

## e. Informasi Database

Inisialisasi database dilakukan secara otomatis saat container pertama kali dijalankan melalui berkas SQL pada folder `sql/` (`01_users.sql` dan `02_barang.sql`).

- **Database Management System:** MySQL 8.0
- **Nama Database (`DB_NAME`):** `packify`
- **Host Internal (Aplikasi ke Database):** `db`
- **Host Eksternal (Dari Komputer Host / GUI Client):** `localhost` atau `127.0.0.1` (Port `3306`)
- **Username Pengguna (`DB_USER`):** `packifyroot`
- **Password Pengguna (`DB_PASSWORD`):** `Pass123`
- **Root Password (`MYSQL_ROOT_PASSWORD`):** `root`

### Skema Tabel Utama:

1. **`users`**: Menyimpan kredensial dan profil (`id`, `nama`, `alamat`, `telpon`, `email`, `password_hash`, `role`). Role: `'pelanggan'` atau `'kurir'`.
2. **`barang`**: Menyimpan pesanan paket (`id_barang`, `id_pengirim`, `nama_penerima`, `berat_barang_kg`, `jumlah_barang`, `alamat_tujuan`, `status`, `id_kurir`, `created_at`, `updated_at`). Status: `'belum_dikirim'`, `'sedang_dikirim'`, atau `'sudah_sampai'`.

---

## f. Informasi Environment & Panduan Matriks Kerentanan

### 1. Spesifikasi Environment Lab

- **Virtualization Engine:** Docker Desktop v4.x+ / Docker Engine v24.x+
- **Container Base OS:** Debian GNU/Linux 12 (Bookworm)
- **Web Runtime:** PHP 8.2 Apache Module (`php:8.2-apache`)
- **Database Engine:** MySQL Server 8.0.x Community Edition
- **Docker Network Bridge:** `packify_default` (Internal Subnet: 172.x.x.x)

---

### 2. Matriks Pemetaan Kerentanan (_Vulnerability Matrix_)

|  No   | Layer              | Kategori Kerentanan                                   | Standar CWE                                                                                                              | Kategori OWASP (2021)                       | Estimasi CVSS v3.1 | Lokasi File Terkait                                                 |
| :---: | :----------------- | :---------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------- | :------------------------------------------ | :----------------: | :------------------------------------------------------------------ |
| **1** | **Application**    | **IDOR** (_Insecure Direct Object Reference_)         | [CWE-639](https://cwe.mitre.org/data/definitions/639.html)                                                               | A01:2021 – Broken Access Control            |   **7.1 (High)**   | `customer-dashboard.php`<br>`edit_barang.php`<br>`hapus_barang.php` |
| **2** | **Application**    | **Broken Authentication** (_Old Password Bypass_)     | [CWE-287](https://cwe.mitre.org/data/definitions/287.html)<br>[CWE-306](https://cwe.mitre.org/data/definitions/306.html) | A07:2021 – Identification & Auth Failures   |   **7.5 (High)**   | `customer-dashboard.php`<br>`courier-dashboard.php`<br>`edit.php`   |
| **3** | **Component**      | **Vulnerable Dependency** (jQuery 3.4.1)              | [CWE-79](https://cwe.mitre.org/data/definitions/79.html)                                                                 | A06:2021 – Vulnerable & Outdated Components |  **6.1 (Medium)**  | `customer-dashboard.php`<br>`courier-dashboard.php`<br>`login.php`  |
| **4** | **Infrastructure** | **Excessive Container Privilege** (_Running as Root_) | [CWE-250](https://cwe.mitre.org/data/definitions/250.html)                                                               | CIS Docker Benchmark §4.1                   |  **6.8 (Medium)**  | `Dockerfile`                                                        |
| **5** | **Network**        | **Exposed Database Port** (MySQL 3306 on Host)        | [CWE-200](https://cwe.mitre.org/data/definitions/200.html)<br>[CWE-284](https://cwe.mitre.org/data/definitions/284.html) | CIS Network Architecture Standard           |  **6.5 (Medium)**  | `docker-compose.yml`                                                |

---

### 3. Panduan Teknis & Pengujian Kerentanan (_Step-by-Step Assessment Guide_)

```
┌────────────────────────────────────────────────────────────────────────┐
│                      SECURITY ASSESSMENT WORKFLOW                      │
│                                                                        │
│  [1. IDOR Test]          ──► Manipulasi ?shipment=1 via Browser URL    │
│  [2. Broken Auth Test]   ──► Ganti Password dengan Current Password acak│
│  [3. Outdated Dep Test]  ──► Audit Console Browser $.fn.jquery         │
│  [4. Container Audit]    ──► Eksekusi 'whoami' dan 'id' di Container   │
│  [5. Network Port Audit] ──► Test-NetConnection / Nmap Port 3306       │
└────────────────────────────────────────────────────────────────────────┘
```

#### Kerentanan 1: IDOR (_Insecure Direct Object Reference_)

- **Akar Masalah (_Root Cause_):**  
  Pada handler backend `customer-dashboard.php`, query pembaruan (`UPDATE`) dan penghapusan (`DELETE`) paket hanya memeriksa `WHERE id_barang = ?` tanpa memvalidasi apakah `id_pengirim` sama dengan ID pengguna dalam sesi (`$user['id']`).
- **Potongan Kode Rentan:**
  ```php
  // Query tidak membatasi kepemilikan id_pengirim
  $stmt = mysqli_prepare($db, 'UPDATE barang SET nama_penerima = ?, alamat_tujuan = ?, berat_barang_kg = ?, jumlah_barang = ? WHERE id_barang = ?');
  ```
- **Dampak Keamanan:** Pengguna non-otoritas (Pelanggan B) dapat memodifikasi data pengiriman (tujuan, penerima, berat) atau membatalkan pesanan milik Pelanggan A.
- **Langkah Pengujian (_Proof of Concept_):**
  1. Login sebagai **Pelanggan B** (`siti@packify.id` / `Password123!`).
  2. Buka URL langsung pada _address bar_ browser:
     ```text
     http://localhost:8000/customer-dashboard.php?shipment=1
     ```
  3. Modal popup Edit akan langsung memuat data paket ID 1 milik **Budi Santoso**.
  4. Ubah Nama Penerima menjadi `Penerima Hasil IDOR` $\rightarrow$ Klik **Update shipment**.
  5. Sistem menampilkan notifikasi sukses dan data paket berhasil dimodifikasi.
- **Remediasi Defensif (_Remediation_):**
  Tambahkan klausul kepemilikan sesi pada setiap query manipulasi data:
  ```php
  $stmt = mysqli_prepare($db, 'UPDATE barang SET nama_penerima = ?, alamat_tujuan = ? WHERE id_barang = ? AND id_pengirim = ?');
  mysqli_stmt_bind_param($stmt, 'ssii', $nama, $alamat, $idBarang, $user['id']);
  ```

---

#### Kerentanan 2: Broken Authentication (_Password Reset Bypass_)

- **Akar Masalah (_Root Cause_):**  
  Pada fitur ganti kata sandi di modal _Settings_, backend menerima input `old_password` dari pengguna tetapi tidak mengevaluasinya menggunakan fungsi `password_verify()` terhadap hash yang tersimpan di database.
- **Potongan Kode Rentan:**
  ```php
  // Hash baru langsung disimpan tanpa memverifikasi $oldPassword ke database
  $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
  $update = mysqli_prepare($db, 'UPDATE users SET password_hash = ? WHERE id = ?');
  mysqli_stmt_bind_param($update, 'si', $newHash, $user['id']);
  mysqli_stmt_execute($update);
  ```
- **Dampak Keamanan:** Siapapun yang memiliki akses sementara ke sesi login pengguna dapat mengambil alih akun secara permanen (_Account Takeover_) tanpa harus mengetahui kata sandi lama korban.
- **Langkah Pengujian (_Proof of Concept_):**
  1. Login ke dashboard, lalu klik tombol **Settings** pada sidebar (atau avatar profil di kanan atas).
  2. Pada formulir **Change password**:
     - **CURRENT PASSWORD:** Masukkan sembarang teks yang salah (contoh: `passwordsalah123`).
     - **NEW PASSWORD:** Masukkan `PasswordBaru456!`.
     - **CONFIRM NEW PASSWORD:** Masukkan `PasswordBaru456!`.
  3. Klik tombol **Update password**.
  4. Muncul notifikasi hijau **"✓ Password berhasil diubah."**
  5. Lakukan _Logout_, lalu masuk kembali menggunakan kata sandi baru `PasswordBaru456!`. Login berhasil.
- **Remediasi Defensif (_Remediation_):**
  Wajibkan verifikasi hash password lama sebelum melakukan update:
  ```php
  $stmt = mysqli_prepare($db, 'SELECT password_hash FROM users WHERE id = ?');
  mysqli_stmt_bind_param($stmt, 'i', $user['id']);
  mysqli_stmt_execute($stmt);
  $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
      set_flash('danger', 'Password lama tidak sesuai.');
      header('Location: customer-dashboard.php');
      exit;
  }
  ```

---

#### Kerentanan 3: Vulnerable & Outdated Dependency (_CVE-2020-11022 & CVE-2020-11023_)

- **Akar Masalah (_Root Cause_):**  
  Aplikasi memuat pustaka frontend pihak ketiga **jQuery v3.4.1** melalui CDN tag `<script>`.
- **Potongan Kode Rentan:**
  ```html
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  ```
- **Dampak Keamanan:** Versi jQuery sebelum `3.5.0` mengandung celah regex parsing HTML pada method seperti `.html()`, `.append()`, dan pengolahan elemen DOM yang memungkinkan eksekusi _Cross-Site Scripting_ (DOM-based XSS).
- **Langkah Pengujian (_Proof of Concept_):**
  1. Buka halaman dashboard di browser.
  2. Buka Developer Tools (`F12` atau `Ctrl + Shift + I`) $\rightarrow$ pilih tab **Console**.
  3. Ketik perintah:
     ```javascript
     $.fn.jquery;
     ```
  4. Output mengembalikan string `"3.4.1"` (membuktikan penggunaan komponen usang yang terdaftar di database NVD).
- **Remediasi Defensif (_Remediation_):**
  Perbarui pustaka ke versi stabil terbaru (misalnya jQuery v3.7.1) atau hapus dependensi dan gunakan Vanilla JavaScript modern:
  ```html
  <script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"
  ></script>
  ```

---

#### Kerentanan 4: Excessive Container Privilege (_Running Container as Root_)

- **Akar Masalah (_Root Cause_):**  
  File `Dockerfile` tidak menyertakan arahan `USER` untuk menurunkan hak akses proses daemon Apache/PHP ke pengguna terbatas (_non-root_).
- **Potongan Konfigurasi Rentan:**
  ```dockerfile
  FROM php:8.2-apache
  RUN docker-php-ext-install mysqli pdo pdo_mysql
  COPY . /var/www/html/
  RUN a2enmod rewrite
  EXPOSE 80
  # Kurang: USER www-data
  ```
- **Dampak Keamanan:** Jika terjadi celah RCE (_Remote Code Execution_) atau _Arbitrary File Upload_, penyerang langsung menguasai container dengan hak superuser (`root`, UID: 0) dan mempermudah teknik _container breakout_.
- **Langkah Pengujian (_Proof of Concept_):**
  1. Jalankan perintah audit berikut di terminal host:
     ```bash
     docker compose exec app whoami
     docker compose exec app id
     ```
  2. Terminal akan menampilkan output:
     ```text
     root
     uid=0(root) gid=0(root) groups=0(root)
     ```
- **Remediasi Defensif (_Remediation_):**
  Atur hak kepemilikan direktori web dan aktifkan user non-root pada `Dockerfile`:
  ```dockerfile
  RUN chown -R www-data:www-data /var/www/html
  USER www-data
  ```

---

#### Kerentanan 5: Exposed Database Port (_Network Misconfiguration_)

- **Akar Masalah (_Root Cause_):**  
  File `docker-compose.yml` mempublikasikan port database MySQL `3306` ke interface publik host (`0.0.0.0:3306->3306/tcp`).
- **Potongan Konfigurasi Rentan:**
  ```yaml
  db:
    image: mysql:8.0
    ports:
      - "3306:3306" # Mengekspos port database ke seluruh interface host
  ```
- **Dampak Keamanan:** Port database terbuka ke jaringan luar / LAN, memungkinkan penyerang melakukan serangan _brute-force_ kredensial MySQL secara langsung atau eksploitasi celah DBMS tanpa melalui lapisan aplikasi web.
- **Langkah Pengujian (_Proof of Concept_):**
  1. Uji keterbukaan port dari PowerShell di host:
     ```powershell
     Test-NetConnection -ComputerName localhost -Port 3306
     ```
     _Output:_ `TcpTestSucceeded : True`.
  2. Atau lakukan port scanning menggunakan Nmap:
     ```bash
     nmap -p 3306 localhost
     ```
     _Output:_ `3306/tcp open mysql`.
  3. Coba sambungkan database client eksternal (DBeaver / MySQL Workbench) ke `localhost:3306` dengan user `packifyroot` dan password `Pass123`. Koneksi berhasil terbuka dari luar container.
- **Remediasi Defensif (_Remediation_):**
  Hapus baris `ports` pada service `db` di `docker-compose.yml`. Container `app` tetap dapat berkomunikasi dengan `db` secara aman melalui _Docker internal bridge network_.

---

## Struktur Direktori Proyek

```text
Packify/
├── assets/
│   ├── css/
│   │   ├── dashboard.css       # Stylesheet utama customer & courier dashboard
│   │   └── login.css           # Stylesheet landing page, login, dan register
│   └── img/                    # Aset grafis & ikon
├── partials/                   # Komponen template parsial
├── service/
│   └── database.php            # Konfigurasi koneksi database MySQL mysqli
├── sql/
│   ├── 01_users.sql            # Skema DDL tabel users & data seeding akun default
│   └── 02_barang.sql           # Skema DDL tabel barang & data seeding paket default
├── courier-dashboard.php       # Dashboard operasional kurir
├── courier-update-shipment.php # Handler pembaruan status pengiriman kurir
├── customer-dashboard.php      # Dashboard layanan pelanggan & pemesanan paket
├── edit_barang.php             # Halaman edit barang (IDOR vulnerable)
├── hapus_barang.php            # Handler pembatalan pesanan (IDOR vulnerable)
├── edit.php                    # Halaman profil & ganti password (Broken auth)
├── functions.php               # Helper fungsi sesi, CSRF, flash message, auth
├── index.php                   # Landing page publik Packify
├── login.php                   # Halaman autentikasi masuk
├── logout.php                  # Handler pemutusan sesi login
├── register.php                # Halaman pendaftaran akun baru
├── Dockerfile                  # Konfigurasi build image container PHP-Apache
├── docker-compose.yml          # Konfigurasi orkestrasi service web & database
└── README.md                   # Dokumentasi teknis & panduan lab (file ini)
```
