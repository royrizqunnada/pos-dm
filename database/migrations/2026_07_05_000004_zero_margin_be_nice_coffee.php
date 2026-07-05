<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Be Nice Coffee (BNC) adalah vendor milik owner sendiri, jadi tidak ada
     * bagi hasil: margin = 0 dan harga dasar = harga jual. Harga jual TETAP.
     *
     * Hanya mengubah data (tetap bisa diedit manual di dashboard menu).
     */
    public function up(): void
    {
        $vendorIds = DB::table('vendors')
            ->where('code', 'BNC')
            ->orWhere('name', 'Be Nice Coffee')
            ->pluck('id');

        if ($vendorIds->isEmpty()) {
            return;
        }

        DB::table('menu_items')
            ->whereIn('vendor_id', $vendorIds)
            ->update([
                'margin' => 0,
                'base_price' => DB::raw('selling_price'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data-only; nilai bisa diatur ulang lewat dashboard menu.
    }
};
