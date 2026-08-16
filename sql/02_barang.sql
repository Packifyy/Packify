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