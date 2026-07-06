<?php

namespace App\Support;

class Brand
{
    /**
     * Palet warna utama DM Kuliner (oranye, selaras logo). Shade gelap
     * dibuat oranye-kecoklatan supaya teks putih di tombol tetap terbaca.
     * Dipakai bersama oleh panel admin & vendor.
     *
     * @return array<int, string>
     */
    public static function primary(): array
    {
        return [
            50 => '#fdf6ec', 100 => '#f9e6c6', 200 => '#f3cd89', 300 => '#eeb14e',
            400 => '#e9971f', 500 => '#d97706', 600 => '#b45309', 700 => '#92400e',
            800 => '#78350f', 900 => '#633012', 950 => '#3f1e0a',
        ];
    }
}
