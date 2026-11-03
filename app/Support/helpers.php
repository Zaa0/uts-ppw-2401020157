<?php

/*
|--------------------------------------------------------------------------
| Helper fungsi sederhana
|--------------------------------------------------------------------------
|
| statusJumlah() dipakai pada Blade untuk menampilkan status jumlah alat
| yang dipinjam: "Banyak" jika jumlah >= 3 dan "Sedikit" jika di bawahnya.
|
*/

if (! function_exists('statusJumlah')) {
    function statusJumlah(int $jumlah): string
    {
        return $jumlah >= 3 ? 'Banyak' : 'Sedikit';
    }
}
