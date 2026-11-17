CREATE DATABASE IF NOT EXISTS 510_merchandise
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 510_merchandise;

CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    harga INT UNSIGNED NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis ENUM('penjualan', 'pemasukan', 'pengeluaran') NOT NULL,
    produk_id INT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 0,
    nominal BIGINT UNSIGNED NOT NULL,
    keterangan VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_produk FOREIGN KEY (produk_id)
        REFERENCES produk(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO produk (nama, harga, stok) VALUES
('Kaos 510', 150000, 50),
('Hoodie 510', 300000, 20),
('Tote Bag 510', 75000, 30),
('Sticker Pack 510', 25000, 100);
