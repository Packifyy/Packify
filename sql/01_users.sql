CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    telpon VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('pelanggan', 'kurir') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default sample users (Password: Password123!)
INSERT INTO users (id, nama, alamat, telpon, email, password_hash, role) VALUES
(1, 'Budi Santoso', 'Jl. Sudirman No. 45, Jakarta Selatan', '081234567890', 'budi@packify.id', '$2y$10$xvsBWd35ua0GgFeHugYHJeWl0.v5dcMLCkdZC6VnB2E3FzXhjfM6.', 'pelanggan'),
(2, 'Siti Rahma', 'Jl. Diponegoro No. 12, Bandung', '081298765432', 'siti@packify.id', '$2y$10$xvsBWd35ua0GgFeHugYHJeWl0.v5dcMLCkdZC6VnB2E3FzXhjfM6.', 'pelanggan'),
(3, 'Ahmad Fauzi', 'Jl. Gatot Subroto No. 88, Jakarta Pusat', '081311223344', 'kurir@packify.id', '$2y$10$xvsBWd35ua0GgFeHugYHJeWl0.v5dcMLCkdZC6VnB2E3FzXhjfM6.', 'kurir')
ON DUPLICATE KEY UPDATE nama=VALUES(nama);