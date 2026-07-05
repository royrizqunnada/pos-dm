<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 1) Kepiting Kompromi (KK): dikosongkan (margin 0, dasar = jual) supaya
     *    owner mengisi margin-nya sendiri nanti lewat dashboard.
     * 2) Menu mahal (> Rp 36.000) yang margin-nya masih 0: margin = 4.000
     *    (dasar = jual - 4.000), KECUALI Kepiting Kompromi.
     *
     * Harga jual TETAP. Data-only, tetap bisa diubah manual di dashboard menu.
     */
    public function up(): void
    {
        $kkIds = DB::table('vendors')
            ->where('code', 'KK')
            ->orWhere('name', 'Kepiting Kompromi')
            ->pluck('id');

        // 1) Kosongkan margin Kepiting Kompromi.
        if ($kkIds->isNotEmpty()) {
            DB::table('menu_items')
                ->whereIn('vendor_id', $kkIds)
                ->update([
                    'margin' => 0,
                    'base_price' => DB::raw('selling_price'),
                    'updated_at' => now(),
                ]);
        }

        // 2) Item > 36.000 yang masih margin 0 -> 4.000 (kecuali KK).
        DB::table('menu_items')
            ->where('selling_price', '>', 36000)
            ->where('margin', 0)
            ->when($kkIds->isNotEmpty(), fn ($q) => $q->whereNotIn('vendor_id', $kkIds->all()))
            ->update([
                'margin' => 4000,
                'base_price' => DB::raw('selling_price - 4000'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data-only; nilai bisa diatur ulang lewat dashboard menu.
    }
};
