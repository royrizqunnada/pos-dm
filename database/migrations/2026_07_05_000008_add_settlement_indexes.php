<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk query settlement/laporan yang memfilter status='paid' +
     * range paid_at (bukan created_at). Di Postgres FK tidak otomatis
     * meng-index kolom referensi, jadi order_items.order_id ditambah manual.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);                  // dashboard (lintas lokasi)
            $table->index(['location_id', 'status', 'paid_at']);   // laporan per lokasi
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'vendor_id']); // join orders + group per vendor
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropIndex(['location_id', 'status', 'paid_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'vendor_id']);
        });
    }
};
