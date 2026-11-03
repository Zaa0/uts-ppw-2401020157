# Hasil Pengujian

Pengujian dilakukan pada `http://127.0.0.1:8002/peminjaman` dengan MySQL
(MariaDB XAMPP) dan Chrome. Data awal diimpor ulang dari
`database/peminjaman_alat_2401020157.sql` sebelum setiap rangkaian uji.

| No | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Bukti |
| --- | --- | --- | --- | --- | --- |
| 1 | Read | Buka `/peminjaman` | Tiga data awal, nama alat, dan status Banyak/Sedikit tampil | Lulus | `01_read.png` |
| 2 | Create valid | Isi PM00000004, Budi Santoso, MHS-2201004, 4, Laptop | Data tersimpan dan feedback sukses tampil | Lulus | `02_create_valid.png` |
| 3 | Create tidak valid | Isi kode `PM01`, nama `Al`, identitas `123` | Pesan validasi tampil dan input lama dipertahankan | Lulus | `03_create_invalid.png` |
| 4 | Duplikat | Simpan kode PM00000001 yang sudah ada | Data ditolak dengan pesan "sudah digunakan" | Lulus | `04_duplikat.png` |
| 5 | Update | Ubah nama menjadi Budi Santoso Putra, jumlah 5, alat Proyektor | Perubahan tersimpan dan feedback tampil | Lulus | `05a_edit_form.png`, `05b_update_success.png` |
| 6 | Konflik update | Ubah nomor identitas menjadi MHS-2201001 milik data lain | Perubahan ditolak dengan pesan "sudah digunakan data lain" | Lulus | `06_update_konflik.png` |
| 7 | Delete | Hapus PM00000004 | Data terhapus dan tabel kembali berisi 3 baris | Lulus | `07_delete.png` |
| 8 | Pencarian | Cari kata kunci `Mira` | Hanya Mira Ananda yang tampil, URL memuat `q=Mira` | Lulus | `08_pencarian.png` |
| 9 | Filter | Pilih alat Proyektor | Hanya Dedi Pranata yang tampil, URL memuat `alat_id=2` | Lulus | `09_filter.png` |
| 10 | Error handling | Hentikan layanan MySQL lalu buka `/peminjaman` | Pengguna melihat pesan aman, detail teknis hanya di log | Lulus | `10_error_handling.png`, `10b_log_terminal.png` |

## Bukti Bug Sebelum Perbaikan

| Bug | Bukti |
| --- | --- |
| `SQLSTATE[HY093]: Invalid parameter number` pada pencarian | `bug_08_pencarian_sebelum_perbaikan.png` |
| Filter ikut berubah setelah Create karena `urlKembali()` membaca `alat_id` | `bug_02_filter_ikut_berubah.png` |
