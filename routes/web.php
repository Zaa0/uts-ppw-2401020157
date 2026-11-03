<?php

use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

// Halaman awal diarahkan ke daftar peminjaman.
Route::redirect('/', '/peminjaman');

// GET  : halaman indeks (form tambah, pencarian, filter, feedback, tabel data)
Route::get('/peminjaman', [PeminjamanController::class, 'index'])
    ->name('peminjaman.index');

// POST : simpan data baru
Route::post('/peminjaman', [PeminjamanController::class, 'store'])
    ->name('peminjaman.store');

// GET  : form edit
Route::get('/peminjaman/{id}/edit', [PeminjamanController::class, 'edit'])
    ->whereNumber('id')
    ->name('peminjaman.edit');

// PUT  : simpan perubahan (method spoofing dari form)
Route::put('/peminjaman/{id}', [PeminjamanController::class, 'update'])
    ->whereNumber('id')
    ->name('peminjaman.update');

// DELETE : hapus data (method spoofing dari form)
Route::delete('/peminjaman/{id}', [PeminjamanController::class, 'destroy'])
    ->whereNumber('id')
    ->name('peminjaman.destroy');
