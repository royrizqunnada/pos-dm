<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ongkir (biaya kirim) untuk pesanan online. Opsional — umumnya 0.
            // Ditagihkan ke pelanggan & masuk kas, TAPI bukan bagian settlement
            // vendor (dihitung dari order_items, bukan kolom ini).
            $table->unsignedInteger('shipping_cost')->default(0)->after('discount_borne_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_cost');
        });
    }
};
