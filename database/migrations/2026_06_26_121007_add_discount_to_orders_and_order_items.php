<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('discount_amount')->default(0)->after('total_amount');
            // Siapa yang menanggung diskon: owner (margin saya) / vendor / split (bagi dua).
            $table->string('discount_borne_by')->nullable()->after('discount_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Alokasi diskon ke baris ini (snapshot, agar settlement tetap akurat).
            $table->unsignedInteger('discount_share')->default(0)->after('line_total');
            $table->unsignedInteger('discount_from_base')->default(0)->after('discount_share');
            $table->unsignedInteger('discount_from_margin')->default(0)->after('discount_from_base');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_borne_by']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_share', 'discount_from_base', 'discount_from_margin']);
        });
    }
};
