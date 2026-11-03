<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Data Peminjaman</title>
</head>
<body>

<h1>Ubah Data Peminjaman Alat</h1>
<p>Praktikum Pemrograman Web &mdash; Paket 2 Tipe B | NIM 2401020157</p>

@if ($feedback)
    <p><strong>Berhasil:</strong> {{ $feedback }}</p>
@endif

@if ($error)
    <p><strong>Gagal:</strong> {{ $error }}</p>
@endif

@if ($errors->any())
    <p><strong>Validasi gagal:</strong></p>
    <ul>
        @foreach ($errors->all() as $pesan)
            <li>{{ $pesan }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('peminjaman.update', ['id' => $peminjaman['id']]) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="q" value="{{ $q }}">
    <input type="hidden" name="filter_alat_id" value="{{ $alatId ?? '' }}">

    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <td><label for="kode_peminjaman">Kode Peminjaman</label></td>
            <td>
                <input type="text" id="kode_peminjaman" name="kode_peminjaman" maxlength="10"
                       value="{{ old('kode_peminjaman', $peminjaman['kode_peminjaman']) }}">
                <small>(tepat 10 karakter huruf/angka)</small>
            </td>
        </tr>
        <tr>
            <td><label for="nama_peminjam">Nama Peminjam</label></td>
            <td>
                <input type="text" id="nama_peminjam" name="nama_peminjam" maxlength="100"
                       value="{{ old('nama_peminjam', $peminjaman['nama_peminjam']) }}">
                <small>(3&ndash;100 karakter)</small>
            </td>
        </tr>
        <tr>
            <td><label for="nomor_identitas">Nomor Identitas</label></td>
            <td>
                <input type="text" id="nomor_identitas" name="nomor_identitas" maxlength="30"
                       value="{{ old('nomor_identitas', $peminjaman['nomor_identitas']) }}">
                <small>(5&ndash;30 karakter, harus unik)</small>
            </td>
        </tr>
        <tr>
            <td><label for="jumlah_alat">Jumlah Alat</label></td>
            <td>
                <input type="number" id="jumlah_alat" name="jumlah_alat" min="1" max="10"
                       value="{{ old('jumlah_alat', $peminjaman['jumlah_alat']) }}">
                <small>(bilangan bulat 1&ndash;10)</small>
            </td>
        </tr>
        <tr>
            <td><label for="alat_id">Alat</label></td>
            <td>
                <select id="alat_id" name="alat_id">
                    <option value="">-- pilih alat --</option>
                    @foreach ($daftarAlat as $alat)
                        <option value="{{ $alat['id'] }}"
                            @selected((string) old('alat_id', $peminjaman['alat_id']) === (string) $alat['id'])>
                            {{ $alat['nama_alat'] }}
                        </option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <button type="submit">Simpan Perubahan</button>
                <a href="{{ route('peminjaman.index', ['q' => $q, 'alat_id' => $alatId]) }}">Batal</a>
            </td>
        </tr>
    </table>
</form>

<hr>

<p>ID data: {{ $peminjaman['id'] }} | Status jumlah saat ini:
    @if (statusJumlah((int) $peminjaman['jumlah_alat']) === 'Banyak')
        Banyak
    @else
        Sedikit
    @endif
</p>

</body>
</html>
