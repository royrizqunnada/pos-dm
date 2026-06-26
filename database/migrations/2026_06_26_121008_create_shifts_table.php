<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_cash')->default(0); // kas awal (modal laci)
            // Snapshot rekonsiliasi saat tutup shift.
            $table->unsignedInteger('expected_cash')->default(0);  // kas seharusnya = kas awal + penjualan tunai
            $table->unsignedInteger('counted_cash')->default(0);   // kas fisik dihitung
            $table->integer('cash_variance')->default(0);          // counted - expected (bisa minus)
            $table->unsignedInteger('total_cash_sales')->default(0);
            $table->unsignedInteger('total_qris_sales')->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'cashier_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('cashier_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });

        Schema::dropIfExists('shifts');
    }
};
