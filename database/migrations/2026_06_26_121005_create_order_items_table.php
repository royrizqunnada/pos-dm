<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name_snapshot'); // nama menu saat transaksi
            $table->unsignedInteger('qty');
            // Snapshot harga WAJIB — laporan settlement harus tetap akurat
            // walau harga menu diubah di kemudian hari.
            $table->unsignedInteger('base_price_snapshot');
            $table->unsignedInteger('margin_snapshot');
            $table->unsignedInteger('selling_price_snapshot');
            $table->unsignedInteger('line_total'); // selling_price_snapshot * qty
            $table->timestamps();

            $table->index(['vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
