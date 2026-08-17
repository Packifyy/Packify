CREATE TABLE IF NOT EXISTS barang (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    id_pengirim INT NOT NULL,
    nama_penerima VARCHAR(150) NOT NULL,
    berat_barang_kg INT NOT NULL,
    jumlah_barang INT NOT NULL,
    alamat_tujuan TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'belum_dikirim',
    id_kurir INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengirim) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_kurir) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default sample shipments
INSERT INTO barang (id_barang, id_pengirim, nama_penerima, berat_barang_kg, jumlah_barang, alamat_tujuan, status, id_kurir) VALUES
(1, 1, 'Dewi Lestari', 2, 1, 'Jl. Pemuda No. 15, Surabaya', 'belum_dikirim', NULL),
(2, 1, 'Rian Hidayat', 5, 2, 'Jl. Merdeka No. 20, Yogyakarta', 'sedang_dikirim', 3),
(3, 2, 'Andi Pratama', 3, 1, 'Jl. Asia Afrika No. 7, Bandung', 'belum_dikirim', NULL)
ON DUPLICATE KEY UPDATE nama_penerima=VALUES(nama_penerima);