-- =====================================================================
-- UTS Praktikum Pemrograman Web - Paket 2 / Tipe B (Sistem Peminjaman Alat)
-- Nama : Andrian Yuza Swanda
-- NIM  : 2401020157
-- Basis data: peminjaman_alat_2401020157
-- =====================================================================

CREATE DATABASE IF NOT EXISTS peminjaman_alat_2401020157
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE peminjaman_alat_2401020157;

-- Tabel master (satu)
DROP TABLE IF EXISTS peminjaman_alat;
DROP TABLE IF EXISTS alat;

CREATE TABLE alat (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_alat VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_alat_nama_alat (nama_alat)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Tabel transaksi (banyak)
CREATE TABLE peminjaman_alat (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_peminjaman CHAR(10) NOT NULL,
    nama_peminjam VARCHAR(100) NOT NULL,
    nomor_identitas VARCHAR(30) NOT NULL,
    jumlah_alat TINYINT UNSIGNED NOT NULL,
    alat_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_peminjaman_kode (kode_peminjaman),
    UNIQUE KEY uq_peminjaman_identitas (nomor_identitas),
    KEY idx_peminjaman_alat (alat_id),
    CONSTRAINT chk_jumlah_alat CHECK (jumlah_alat BETWEEN 1 AND 10),
    CONSTRAINT fk_peminjaman_alat FOREIGN KEY (alat_id)
        REFERENCES alat (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Data awal wajib
INSERT INTO alat (nama_alat) VALUES
    ('Kamera'),
    ('Proyektor'),
    ('Laptop');

INSERT INTO peminjaman_alat
    (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id)
VALUES
    ('PM00000001', 'Rina Kurnia',  'MHS-2201001', 2, 1),
    ('PM00000002', 'Dedi Pranata', 'MHS-2201002', 1, 2),
    ('PM00000003', 'Mira Ananda',  'MHS-2201003', 3, 1);
