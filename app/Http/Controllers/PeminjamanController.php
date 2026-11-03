<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PDO;
use PDOException;
use Throwable;

class PeminjamanController extends Controller
{
    /**
     * Batas jumlah alat yang boleh dipinjam dalam satu transaksi.
     */
    private const MIN_JUMLAH = 1;

    private const MAX_JUMLAH = 10;

    /**
     * Ambil koneksi PDO dari koneksi Laravel.
     */
    private function pdo(): PDO
    {
        return DB::connection()->getPdo();
    }

    /**
     * Halaman indeks: form tambah, pencarian, filter, feedback, dan tabel data.
     */
    public function index(Request $request): View
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $alatId = $request->query('alat_id');
        $alatId = ($alatId === null || $alatId === '') ? null : (int) $alatId;

        $feedback = session('feedback');
        $error = session('error');

        $daftarAlat = [];
        $daftarPeminjaman = [];

        try {
            $pdo = $this->pdo();

            $stmtAlat = $pdo->query('SELECT id, nama_alat FROM alat ORDER BY nama_alat ASC');
            $daftarAlat = $stmtAlat->fetchAll();

            $sql = 'SELECT p.id, p.kode_peminjaman, p.nama_peminjam, p.nomor_identitas,
                           p.jumlah_alat, p.alat_id, a.nama_alat
                    FROM peminjaman_alat AS p
                    INNER JOIN alat AS a ON a.id = p.alat_id
                    WHERE 1 = 1';
            $parameter = [];

            if ($q !== '') {
                $sql .= ' AND (p.kode_peminjaman LIKE :kode
                             OR p.nama_peminjam LIKE :nama
                             OR p.nomor_identitas LIKE :identitas)';
                $parameter[':kode'] = '%'.$q.'%';
                $parameter[':nama'] = '%'.$q.'%';
                $parameter[':identitas'] = '%'.$q.'%';
            }

            if ($alatId !== null) {
                $sql .= ' AND p.alat_id = :alat_id';
                $parameter[':alat_id'] = $alatId;
            }

            $sql .= ' ORDER BY p.id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($parameter);
            $daftarPeminjaman = $stmt->fetchAll();
        } catch (Throwable $e) {
            report($e);
            $error = 'Terjadi kesalahan saat memuat data. Silakan coba lagi.';
        }

        return view('peminjaman.index', [
            'daftarAlat' => $daftarAlat,
            'daftarPeminjaman' => $daftarPeminjaman,
            'q' => $q,
            'alatId' => $alatId,
            'feedback' => $feedback,
            'error' => $error,
        ]);
    }

    /**
     * Simpan data peminjaman baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->sanitasi($request);
        $kembaliForm = $this->urlKembali($request, true);
        $kembali = $this->urlKembali($request);

        $validator = Validator::make($data, $this->aturanValidasi(), $this->pesanValidasi());

        if ($validator->fails()) {
            return redirect($kembaliForm)->withErrors($validator)->withInput();
        }

        try {
            $pdo = $this->pdo();

            if ($this->sudahDipakai($pdo, 'kode_peminjaman', $data['kode_peminjaman'])) {
                return redirect($kembali)->withInput()
                    ->with('error', 'Kode peminjaman '.$data['kode_peminjaman'].' sudah digunakan.');
            }

            if ($this->sudahDipakai($pdo, 'nomor_identitas', $data['nomor_identitas'])) {
                return redirect($kembali)->withInput()
                    ->with('error', 'Nomor identitas '.$data['nomor_identitas'].' sudah digunakan.');
            }

            if (! $this->alatTersedia($pdo, (int) $data['alat_id'])) {
                return redirect($kembali)->withInput()
                    ->with('error', 'Alat yang dipilih tidak tersedia.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO peminjaman_alat
                    (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id)
                 VALUES (:kode_peminjaman, :nama_peminjam, :nomor_identitas, :jumlah_alat, :alat_id)'
            );

            $stmt->execute([
                ':kode_peminjaman' => $data['kode_peminjaman'],
                ':nama_peminjam' => $data['nama_peminjam'],
                ':nomor_identitas' => $data['nomor_identitas'],
                ':jumlah_alat' => (int) $data['jumlah_alat'],
                ':alat_id' => (int) $data['alat_id'],
            ]);

            return redirect($this->urlKembali($request))
                ->with('feedback', 'Data peminjaman '.$data['kode_peminjaman'].' berhasil ditambahkan.');
        } catch (PDOException $e) {
            report($e);

            if ($e->getCode() === '23000') {
                return redirect($kembali)->withInput()
                    ->with('error', 'Kode peminjaman atau nomor identitas sudah digunakan.');
            }

            return redirect($kembali)->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        } catch (Throwable $e) {
            report($e);

            return redirect($kembali)->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    /**
     * Tampilkan form edit.
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $alatId = $request->query('alat_id');
        $alatId = ($alatId === null || $alatId === '') ? null : (int) $alatId;

        try {
            $pdo = $this->pdo();

            $stmt = $pdo->prepare(
                'SELECT id, kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id
                 FROM peminjaman_alat
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $peminjaman = $stmt->fetch();

            if ($peminjaman === false) {
                return redirect()->route('peminjaman.index')
                    ->with('error', 'Data peminjaman tidak ditemukan.');
            }

            $stmtAlat = $pdo->query('SELECT id, nama_alat FROM alat ORDER BY nama_alat ASC');
            $daftarAlat = $stmtAlat->fetchAll();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('peminjaman.index')
                ->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }

        return view('peminjaman.edit', [
            'peminjaman' => $peminjaman,
            'daftarAlat' => $daftarAlat,
            'q' => $q,
            'alatId' => $alatId,
            'error' => session('error'),
            'feedback' => session('feedback'),
        ]);
    }

    /**
     * Simpan perubahan data peminjaman.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $this->sanitasi($request);
        $kembaliEdit = $this->urlEdit($request, $id);

        $validator = Validator::make($data, $this->aturanValidasi(), $this->pesanValidasi());

        if ($validator->fails()) {
            return redirect($kembaliEdit)->withErrors($validator)->withInput();
        }

        try {
            $pdo = $this->pdo();

            if (! $this->peminjamanAda($pdo, $id)) {
                return redirect($this->urlKembali($request))
                    ->with('error', 'Data peminjaman tidak ditemukan.');
            }

            if ($this->sudahDipakai($pdo, 'kode_peminjaman', $data['kode_peminjaman'], $id)) {
                return redirect($kembaliEdit)->withInput()
                    ->with('error', 'Kode peminjaman '.$data['kode_peminjaman'].' sudah digunakan data lain.');
            }

            if ($this->sudahDipakai($pdo, 'nomor_identitas', $data['nomor_identitas'], $id)) {
                return redirect($kembaliEdit)->withInput()
                    ->with('error', 'Nomor identitas '.$data['nomor_identitas'].' sudah digunakan data lain.');
            }

            if (! $this->alatTersedia($pdo, (int) $data['alat_id'])) {
                return redirect($kembaliEdit)->withInput()
                    ->with('error', 'Alat yang dipilih tidak tersedia.');
            }

            $stmt = $pdo->prepare(
                'UPDATE peminjaman_alat
                 SET kode_peminjaman = :kode_peminjaman,
                     nama_peminjam = :nama_peminjam,
                     nomor_identitas = :nomor_identitas,
                     jumlah_alat = :jumlah_alat,
                     alat_id = :alat_id
                 WHERE id = :id'
            );

            $stmt->execute([
                ':kode_peminjaman' => $data['kode_peminjaman'],
                ':nama_peminjam' => $data['nama_peminjam'],
                ':nomor_identitas' => $data['nomor_identitas'],
                ':jumlah_alat' => (int) $data['jumlah_alat'],
                ':alat_id' => (int) $data['alat_id'],
                ':id' => $id,
            ]);

            return redirect($this->urlKembali($request))
                ->with('feedback', 'Data peminjaman '.$data['kode_peminjaman'].' berhasil diperbarui.');
        } catch (PDOException $e) {
            report($e);

            if ($e->getCode() === '23000') {
                return redirect($kembaliEdit)->withInput()
                    ->with('error', 'Kode peminjaman atau nomor identitas sudah digunakan data lain.');
            }

            return redirect($kembaliEdit)->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.');
        } catch (Throwable $e) {
            report($e);

            return redirect($kembaliEdit)->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.');
        }
    }

    /**
     * Hapus data peminjaman.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $kembali = $this->urlKembali($request);

        try {
            $pdo = $this->pdo();

            $stmt = $pdo->prepare('DELETE FROM peminjaman_alat WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return redirect($kembali)->with('error', 'Data peminjaman tidak ditemukan.');
            }

            return redirect($kembali)->with('feedback', 'Data peminjaman berhasil dihapus.');
        } catch (Throwable $e) {
            report($e);

            return redirect($kembali)
                ->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Bersihkan input teks: trim dan strip_tags.
     *
     * @return array<string, string>
     */
    private function sanitasi(Request $request): array
    {
        return [
            'kode_peminjaman' => trim(strip_tags((string) $request->input('kode_peminjaman', ''))),
            'nama_peminjam' => trim(strip_tags((string) $request->input('nama_peminjam', ''))),
            'nomor_identitas' => trim(strip_tags((string) $request->input('nomor_identitas', ''))),
            'jumlah_alat' => trim(strip_tags((string) $request->input('jumlah_alat', ''))),
            'alat_id' => trim(strip_tags((string) $request->input('alat_id', ''))),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function aturanValidasi(): array
    {
        return [
            'kode_peminjaman' => ['required', 'string', 'size:10', 'alpha_num'],
            'nama_peminjam' => ['required', 'string', 'min:3', 'max:100'],
            'nomor_identitas' => ['required', 'string', 'min:5', 'max:30'],
            'jumlah_alat' => ['required', 'integer', 'between:'.self::MIN_JUMLAH.','.self::MAX_JUMLAH],
            'alat_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'kode_peminjaman.required' => 'Kode peminjaman wajib diisi.',
            'kode_peminjaman.size' => 'Kode peminjaman harus tepat 10 karakter.',
            'kode_peminjaman.alpha_num' => 'Kode peminjaman hanya boleh berisi huruf dan angka.',
            'nama_peminjam.required' => 'Nama peminjam wajib diisi.',
            'nama_peminjam.min' => 'Nama peminjam minimal 3 karakter.',
            'nama_peminjam.max' => 'Nama peminjam maksimal 100 karakter.',
            'nomor_identitas.required' => 'Nomor identitas wajib diisi.',
            'nomor_identitas.min' => 'Nomor identitas minimal 5 karakter.',
            'nomor_identitas.max' => 'Nomor identitas maksimal 30 karakter.',
            'jumlah_alat.required' => 'Jumlah alat wajib diisi.',
            'jumlah_alat.integer' => 'Jumlah alat harus berupa bilangan bulat.',
            'jumlah_alat.between' => 'Jumlah alat harus antara 1 sampai 10.',
            'alat_id.required' => 'Alat wajib dipilih.',
            'alat_id.integer' => 'Alat yang dipilih tidak valid.',
            'alat_id.min' => 'Alat wajib dipilih.',
        ];
    }

    /**
     * Cek nilai unik dengan prepared statement.
     * Pada update, data milik sendiri (id) dikecualikan.
     */
    private function sudahDipakai(PDO $pdo, string $kolom, string $nilai, ?int $kecualiId = null): bool
    {
        $kolomAman = in_array($kolom, ['kode_peminjaman', 'nomor_identitas'], true)
            ? $kolom
            : 'kode_peminjaman';

        $sql = 'SELECT COUNT(*) FROM peminjaman_alat WHERE '.$kolomAman.' = :nilai';
        $parameter = [':nilai' => $nilai];

        if ($kecualiId !== null) {
            $sql .= ' AND id <> :kecuali_id';
            $parameter[':kecuali_id'] = $kecualiId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parameter);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function alatTersedia(PDO $pdo, int $alatId): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM alat WHERE id = :id');
        $stmt->execute([':id' => $alatId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function peminjamanAda(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM peminjaman_alat WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * URL indeks sambil mempertahankan kata kunci dan filter.
     * $keForm = true menambahkan anchor ke form tambah (untuk pesan validasi).
     */
    private function urlKembali(Request $request, bool $keForm = false): string
    {
        $parameter = [];

        $q = trim(strip_tags((string) $request->input('q', '')));
        if ($q !== '') {
            $parameter['q'] = $q;
        }

        $alatId = $request->input('filter_alat_id');
        if ($alatId !== null && $alatId !== '') {
            $parameter['alat_id'] = (int) $alatId;
        }

        $url = route('peminjaman.index', $parameter);

        return $keForm ? $url.'#form-tambah' : $url;
    }

    /**
     * URL form edit sambil mempertahankan kata kunci dan filter.
     */
    private function urlEdit(Request $request, int $id): string
    {
        $parameter = ['id' => $id];

        $q = trim(strip_tags((string) $request->input('q', '')));
        if ($q !== '') {
            $parameter['q'] = $q;
        }

        $alatId = $request->input('filter_alat_id');
        if ($alatId !== null && $alatId !== '') {
            $parameter['alat_id'] = (int) $alatId;
        }

        return route('peminjaman.edit', $parameter);
    }
}
