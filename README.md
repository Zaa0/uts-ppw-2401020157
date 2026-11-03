# Sistem Peminjaman Alat Laboratorium

UTS Praktikum Pemrograman Web — Paket 2, Tipe B.

| Keterangan | Isi |
| --- | --- |
| Nama | Andrian Yuza Swanda |
| NIM | 2401020157 |
| Mata kuliah | Praktikum Pemrograman Web (INF11197) |
| Tipe soal | Paket 2 — Tipe B (Sistem Peminjaman Alat) |
| Teknologi | Laravel 13, PHP 8.3, MySQL/MariaDB, PDO, Blade |
| Basis data | `peminjaman_alat_2401020157` |
| Route utama | `/peminjaman` |

Aplikasi CRUD sederhana untuk mencatat peminjaman alat laboratorium. Halaman
indeks memuat form tambah, pencarian, filter alat, pesan feedback, dan tabel
data. Tampilan hanya memakai HTML dan Blade tanpa CSS, JavaScript, Bootstrap,
atau Tailwind.

## Fitur

- **Create** — menambah data peminjaman beserta validasi server-side.
- **Read** — menampilkan seluruh data peminjaman yang di-join dengan tabel alat.
- **Update** — mengubah data melalui form edit dengan method spoofing `PUT`.
- **Delete** — menghapus data melalui method spoofing `DELETE`.
- **Pencarian** — mencari berdasarkan `kode_peminjaman`, `nama_peminjam`, atau
  `nomor_identitas`.
- **Filter** — menyaring data berdasarkan `alat_id`.
- **Status jumlah** — fungsi `statusJumlah()` menampilkan `Banyak` jika jumlah
  alat minimal 3 dan `Sedikit` jika kurang dari 3.
- **Feedback & error** — pesan berhasil/gagal ditampilkan di halaman, sedangkan
  detail teknis hanya dicatat ke `storage/logs/laravel.log` melalui `report()`.

## Struktur Data

```
alat
├── id            BIGINT UNSIGNED  PK, AUTO_INCREMENT
└── nama_alat     VARCHAR(100)     NOT NULL, UNIQUE

peminjaman_alat
├── id               BIGINT UNSIGNED  PK, AUTO_INCREMENT
├── kode_peminjaman  CHAR(10)         NOT NULL, UNIQUE
├── nama_peminjam    VARCHAR(100)     NOT NULL
├── nomor_identitas  VARCHAR(30)      NOT NULL, UNIQUE
├── jumlah_alat      TINYINT UNSIGNED NOT NULL, CHECK (1..10)
├── alat_id          BIGINT UNSIGNED  NOT NULL, FK -> alat.id
├── created_at       TIMESTAMP        NOT NULL
└── updated_at       TIMESTAMP        NOT NULL
```

Foreign key `alat_id` memakai `ON UPDATE CASCADE` dan `ON DELETE RESTRICT`.
Relasi: satu `alat` dapat dipakai oleh banyak `peminjaman_alat`
(one-to-many).

## Route

| Method | URI | Aksi | Nama Route |
| --- | --- | --- | --- |
| GET | `/` | Redirect ke `/peminjaman` | — |
| GET | `/peminjaman` | Daftar + form tambah + pencarian + filter | `peminjaman.index` |
| POST | `/peminjaman` | Simpan data baru | `peminjaman.store` |
| GET | `/peminjaman/{id}/edit` | Form edit | `peminjaman.edit` |
| PUT | `/peminjaman/{id}` | Simpan perubahan | `peminjaman.update` |
| DELETE | `/peminjaman/{id}` | Hapus data | `peminjaman.destroy` |

## Keamanan dan Validasi

- Semua input teks dibersihkan dengan `trim()` dan `strip_tags()`.
- Validasi server-side memakai `Validator` Laravel:
  - `kode_peminjaman`: wajib, tepat 10 karakter, alfanumerik.
  - `nama_peminjam`: wajib, 3–100 karakter.
  - `nomor_identitas`: wajib, 5–30 karakter, unik.
  - `jumlah_alat`: wajib, bilangan bulat 1–10.
  - `alat_id`: wajib, harus ada di tabel `alat`.
- Semua query yang memuat input pengguna memakai **PDO prepared statement**
  dari koneksi Laravel (`DB::connection()->getPdo()`), tanpa konkatenasi nilai.
- Setiap operasi database dibungkus `try–catch`. Exception dicatat dengan
  `report()`, sedangkan pengguna hanya melihat pesan aman.
- Form memakai `@csrf`; update dan delete memakai `@method('PUT')` dan
  `@method('DELETE')`.
- Nilai lama dipertahankan dengan `old()` saat validasi gagal.

## Cara Menjalankan

1. **Prasyarat**: PHP 8.2+, Composer, MySQL/MariaDB (XAMPP/Laragon).
2. **Salin konfigurasi**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan
   `DB_PASSWORD` bila perlu. Berkas `.env` tidak disertakan di repositori.
3. **Impor basis data** (membuat database, tabel, constraint, dan data awal):
   ```bash
   mysql -u root < database/peminjaman_alat_2401020157.sql
   ```
4. **Pasang dependensi dan jalankan**:
   ```bash
   composer install
   php artisan serve --port=8002
   ```
5. Buka `http://127.0.0.1:8002/peminjaman`.

## Data Awal

| Kode | Nama Peminjam | Nomor Identitas | Jumlah | Alat |
| --- | --- | --- | --- | --- |
| PM00000001 | Rina Kurnia | MHS-2201001 | 2 | Kamera |
| PM00000002 | Dedi Pranata | MHS-2201002 | 1 | Proyektor |
| PM00000003 | Mira Ananda | MHS-2201003 | 3 | Kamera |

## Pengujian

Sepuluh skenario diuji melalui browser dan terminal:

1. Read — tiga data awal tampil beserta nama alat dan status jumlah.
2. Create valid — data baru tersimpan dan feedback muncul.
3. Create tidak valid — pesan validasi tampil dan `old()` dipertahankan.
4. Duplikat — kode/nomor identitas yang sudah dipakai ditolak.
5. Update — form edit terisi data lama dan perubahan tersimpan.
6. Konflik update — nomor identitas milik data lain ditolak.
7. Delete — data terhapus dan jumlah baris berkurang.
8. Pencarian — hasil sesuai kata kunci dan URL memuat `q`.
9. Filter — hasil sesuai alat dan URL memuat `alat_id`.
10. Error handling — saat MySQL dimatikan, pengguna melihat pesan aman tanpa
    detail teknis, sedangkan detail dicatat di log.

Bukti screenshot tersedia di `docs/screenshots/`.

## Catatan Perbaikan

Dua bug ditemukan saat pengujian dan diperbaiki:

1. **`SQLSTATE[HY093]: Invalid parameter number`** pada pencarian. Nama
   placeholder `:kata_kunci` dipakai tiga kali pada native prepared statement.
   Perbaikan: setiap kolom memakai placeholder berbeda (`:kode`, `:nama`,
   `:identitas`).
2. **Filter ikut berubah setelah Create/Update**. `urlKembali()` membaca
   `alat_id` (nilai pilihan pada form) sehingga filter berubah. Perbaikan:
   memakai input tersembunyi `filter_alat_id` yang hanya berisi filter aktif.

## Lisensi

Dibuat untuk keperluan UTS Praktikum Pemrograman Web.
