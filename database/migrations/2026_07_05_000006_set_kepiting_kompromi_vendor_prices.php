<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi harga dasar (harga vendor) & margin untuk menu Kepiting Kompromi.
     * Margin = harga jual - harga vendor. Harga jual & NAMA MENU tetap;
     * hanya base_price & margin yang diisi (tetap bisa diubah di dashboard).
     */
    public function up(): void
    {
        // Nama menu (persis seperti di data) => harga vendor (harga dasar).
        $vendorPrices = [
            'Kompromi 1' => 45000,
            'Kompromi 2' => 65000,
            'Kompromi 3' => 95000,
            'Kompromi 4' => 145000,
            'Paket Kompromi Spesial Titanic' => 80000,
            'Kepiting Only 1' => 20000,
            'Kepiting Only 2' => 30000,
            'Kerang Mix' => 30000,
            'Kerang Hijau' => 12000,
            'Kerang Tahu' => 12000,
            'Kerang Dara' => 17000,
            'Kerang Simping' => 17000,
            'Udang' => 25000,
            'Udang Goreng Tepung' => 25000,
            'Cumi' => 25000,
            'Cumi Goreng Tepung' => 25000,
            'Gurita' => 25000,
        ];

        $kkIds = DB::table('vendors')
            ->where('code', 'KK')
            ->orWhere('name', 'Kepiting Kompromi')
            ->pluck('id');

        if ($kkIds->isEmpty()) {
            return;
        }

        foreach ($vendorPrices as $name => $base) {
            $items = DB::table('menu_items')
                ->whereIn('vendor_id', $kkIds->all())
                ->where('name', $name)
                ->get(['id', 'selling_price']);

            foreach ($items as $item) {
                $selling = (int) $item->selling_price;
                // Jaga-jaga bila harga jual < harga vendor: margin tak boleh minus.
                $basePrice = min($base, $selling);
                $margin = $selling - $basePrice;

                DB::table('menu_items')
                    ->where('id', $item->id)
                    ->update([
                        'base_price' => $basePrice,
                        'margin' => $margin,
                        // selling_price tidak diubah (= base_price + margin).
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Data-only; nilai bisa diatur ulang lewat dashboard menu.
    }
};
