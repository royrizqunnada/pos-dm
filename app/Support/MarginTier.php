<?php

namespace App\Support;

/**
 * Aturan margin owner berjenjang (tier) berdasarkan HARGA JUAL.
 *
 * Owner menetapkan harga jual; margin owner ditentukan otomatis dari tier,
 * lalu harga dasar (jatah vendor) = harga jual - margin.
 *
 * Contoh: Bakso jual 14.000 -> tier 11rb–25rb -> margin 3.000 -> dasar 11.000.
 */
class MarginTier
{
    /**
     * Batas ATAS harga jual (inklusif) => margin owner.
     * Diurutkan dari terkecil. Harga di atas batas terakhir dianggap di luar
     * tier (lihat for()).
     *
     * @var array<int, array{max:int, margin:int}>
     */
    public const TIERS = [
        ['max' => 2999, 'margin' => 500],    // mis. Sate (harga < 3rb)
        ['max' => 5999, 'margin' => 1000],   // 3rb – 5rb
        ['max' => 10999, 'margin' => 2000],  // 6rb – 10rb
        ['max' => 25999, 'margin' => 3000],  // 11rb – 25rb
        ['max' => 36000, 'margin' => 4000],  // 26rb – 36rb
    ];

    /**
     * Batas harga jual tertinggi yang punya tier baku.
     */
    public const MAX_TIER_PRICE = 36000;

    /**
     * Margin owner untuk suatu harga jual.
     * Mengembalikan null bila harga <= 0 atau di atas MAX_TIER_PRICE
     * (item premium — margin harus ditentukan manual/terpisah).
     */
    public static function for(int $sellingPrice): ?int
    {
        if ($sellingPrice <= 0) {
            return null;
        }

        foreach (self::TIERS as $tier) {
            if ($sellingPrice <= $tier['max']) {
                return $tier['margin'];
            }
        }

        return null; // di atas 36.000
    }
}
