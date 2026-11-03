<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Peminjaman Alat</title>
</head>
<body>

<h1>Data Peminjaman Alat Laboratorium</h1>
<p>Praktikum Pemrograman Web &mdash; Paket 2 Tipe B | NIM 2401020157</p>

@if ($feedback)
    <p><strong>Berhasil:</strong> {{ $feedback }}</p>
@endif

@if ($error)
    <p><strong>Gagal:</strong> {{ $error }}</p>
@endif

<h2>Pencarian dan Filter</h2>

<form method="GET" action="{{ route('peminjaman.index') }}">
    <label for="q">Kata kunci</label>
    <input type="text" id="q" name="q" value="{{ $q }}" size="30"
           placeholder="kode peminjaman, nama peminjam, atau nomor identitas">

    <label for="filter_alat_id">Alat</label>
    <select id="filter_alat_id" name="alat_id">
        <option value="">Semua alat</option>
        @foreach ($daftarAlat as $alat)
            <option value="{{ $alat['id'] }}" @selected((string) $alatId === (string) $alat['id'])>
                {{ $alat['nama_alat'] }}
            </option>
        @endforeach
    </select>

    <button type="submit">Cari</button>
    <a href="{{ route('peminjaman.index') }}">Reset</a>
</form>

<p>
    Menampilkan {{ count($daftarPeminjaman) }} data
    @if ($q !== '')
        untuk kata kunci &ldquo;{{ $q }}&rdquo;
    @endif
</p>

<h2>Daftar Peminjaman</h2>

<table border="1" cellpadding="3" cellspacing="0">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode</th>
            <th>Nama Peminjam</th>
            <th>No. Identitas</th>
            <th>Jumlah Alat</th>
            <th>Status</th>
            <th>Alat</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($daftarPeminjaman as $nomor => $peminjaman)
            <tr>
                <td>{{ $nomor + 1 }}</td>
                <td>{{ $peminjaman['kode_peminjaman'] }}</td>
                <td>{{ $peminjaman['nama_peminjam'] }}</td>
                <td>{{ $peminjaman['nomor_identitas'] }}</td>
                <td>{{ $peminjaman['jumlah_alat'] }}</td>
                <td>
                    @if (statusJumlah((int) $peminjaman['jumlah_alat']) === 'Banyak')
                        Banyak
                    @else
                        Sedikit
                    @endif
                </td>
                <td>{{ $peminjaman['nama_alat'] }}</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <a href="{{ route('peminjaman.edit', ['id' => $peminjaman['id'], 'q' => $q, 'alat_id' => $alatId]) }}">Ubah</a>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('peminjaman.destroy', ['id' => $peminjaman['id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <input type="hidden" name="filter_alat_id" value="{{ $alatId ?? '' }}">
                                    <button type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">Tidak ada data peminjaman yang sesuai.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<hr>

<h2 id="form-tambah">Form Tambah Peminjaman</h2>
<p><small>Kode: tepat 10 karakter huruf/angka &middot; Nama: 3&ndash;100 karakter &middot;
Nomor identitas: 5&ndash;30 karakter dan unik &middot; Jumlah: bilangan bulat 1&ndash;10 &middot;
Alat wajib dipilih.</small></p>

@if ($errors->any())
    <p><strong>Validasi gagal:</strong></p>
    <ul>
        @foreach ($errors->all() as $pesan)
            <li>{{ $pesan }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('peminjaman.store') }}">
    @csrf
    <input type="hidden" name="q" value="{{ $q }}">
    <input type="hidden" name="filter_alat_id" value="{{ $alatId ?? '' }}">

    <table border="1" cellpadding="3" cellspacing="0">
        <tr>
            <td><label for="kode_peminjaman">Kode Peminjaman</label></td>
            <td><input type="text" id="kode_peminjaman" name="kode_peminjaman" size="12"
                       maxlength="10" value="{{ old('kode_peminjaman') }}"></td>

            <td><label for="nama_peminjam">Nama Peminjam</label></td>
            <td><input type="text" id="nama_peminjam" name="nama_peminjam" size="24"
                       maxlength="100" value="{{ old('nama_peminjam') }}"></td>

            <td><label for="nomor_identitas">Nomor Identitas</label></td>
            <td><input type="text" id="nomor_identitas" name="nomor_identitas" size="14"
                       maxlength="30" value="{{ old('nomor_identitas') }}"></td>
        </tr>
        <tr>
            <td><label for="jumlah_alat">Jumlah Alat</label></td>
            <td><input type="number" id="jumlah_alat" name="jumlah_alat" size="5"
                       min="1" max="10" value="{{ old('jumlah_alat') }}"></td>

            <td><label for="alat_id">Alat</label></td>
            <td>
                <select id="alat_id" name="alat_id">
                    <option value="">-- pilih alat --</option>
                    @foreach ($daftarAlat as $alat)
                        <option value="{{ $alat['id'] }}" @selected((string) old('alat_id') === (string) $alat['id'])>
                            {{ $alat['nama_alat'] }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td colspan="2">
                <button type="submit">Simpan Peminjaman</button>
            </td>
        </tr>
    </table>
</form>

</body>
</html>
