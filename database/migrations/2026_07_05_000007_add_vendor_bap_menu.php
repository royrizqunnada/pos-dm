<?php

use App\Models\MenuItem;
use App\Models\Vendor;
use App\Support\MarginTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah vendor BAP + menunya (harga jual dari poster). Margin owner
     * mengikuti tier standar (MarginTier); harga dasar = jual - margin.
     * Idempotent (updateOrCreate) & tetap bisa diedit di dashboard.
     */
    public function up(): void
    {
        $locationId = DB::table('locations')->orderBy('id')->value('id');

        if (! $locationId) {
            return;
        }

        $vendor = Vendor::firstOrCreate(
            ['location_id' => $locationId, 'code' => 'BAP'],
            ['name' => 'BAP', 'is_active' => true],
        );

        // Kategori => [nama menu => harga jual]
        $menu = [
            'Aneka Menu' => [
                'Sop Iga Sapi' => 31000,
                'Soto Sokaraja Sapi' => 21000,
                'Soto Sokaraja Ayam' => 19000,
                'Soto Semarang' => 17000,
                'Kwetiau Goreng Biasa' => 15000,
                'Kwetiau Goreng Spesial' => 20000,
                'Kwetiau Kuah Biasa' => 15000,
                'Kwetiau Kuah Spesial' => 20000,
                'Mie Tek Tek Biasa' => 12000,
                'Mie Tek Tek Spesial' => 15000,
                'Mie Sosis' => 13000,
                'Pangsit Tek Tek Ayam' => 16000,
                'Pangsit Goreng' => 12000,
                'Extra Nasi BAP' => 5000,
            ],
            'Rice Bowl' => [
                'Chicken Pop Saus BBQ' => 15000,
                'Chicken Pop Kocak' => 15000,
                'Chicken Pop Sambal Matah' => 15000,
                'Chicken Pop Sambal Bawang' => 15000,
                'Chicken Pop Cabe Garam' => 15000,
            ],
        ];

        foreach ($menu as $category => $items) {
            foreach ($items as $name => $selling) {
                $margin = MarginTier::for($selling) ?? 0;
                $base = max(0, $selling - $margin);

                MenuItem::updateOrCreate(
                    ['vendor_id' => $vendor->id, 'name' => $name],
                    [
                        'category' => $category,
                        'base_price' => $base,
                        'margin' => $margin,
                        // selling_price dihitung otomatis oleh model (= base + margin).
                        'is_available' => true,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        // Data-only; hapus manual di dashboard bila perlu.
    }
};
