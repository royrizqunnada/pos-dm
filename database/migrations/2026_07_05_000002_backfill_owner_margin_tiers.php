<?php

use App\Support\MarginTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi margin owner untuk menu yang marginnya masih 0, berdasarkan tier
     * harga jual (lihat App\Support\MarginTier). Harga jual TETAP, hanya
     * harga dasar (jatah vendor) yang disesuaikan: dasar = jual - margin.
     *
     * Hanya mengisi yang KOSONG (margin = 0) supaya margin yang sudah diatur
     * manual tidak tertimpa. Setelah ini, semua tetap bisa diubah di dashboard
     * menu. Item di atas Rp 36.000 dilewati (diatur manual oleh owner).
     */
    public function up(): void
    {
        DB::table('menu_items')
            ->where('margin', 0)
            ->whereBetween('selling_price', [1, MarginTier::MAX_TIER_PRICE])
            ->orderBy('id')
            ->get(['id', 'selling_price'])
            ->each(function ($row): void {
                $margin = MarginTier::for((int) $row->selling_price);

                if ($margin === null) {
                    return;
                }

                DB::table('menu_items')
                    ->where('id', $row->id)
                    ->update([
                        'margin' => $margin,
                        'base_price' => (int) $row->selling_price - $margin,
                        // selling_price sengaja tidak diubah (= dasar + margin).
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * Backfill data satu arah — tidak dibalik otomatis agar tidak menimpa
     * margin yang mungkin sudah diedit owner setelah migrasi berjalan.
     */
    public function down(): void
    {
        // sengaja dibiarkan (no-op)
    }
};
