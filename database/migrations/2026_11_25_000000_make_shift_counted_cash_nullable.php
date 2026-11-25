<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sebelumnya shift bisa ditutup tanpa mengisi kas fisik, sehingga tersimpan
     * counted_cash = 0 dan cash_variance = -expected_cash (selisih "merah" palsu).
     *
     * Sekarang: kas fisik yang tidak dihitung disimpan sebagai NULL, bukan 0,
     * dan data lama yang jelas-jelas tidak dihitung dibersihkan.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->unsignedInteger('counted_cash')->nullable()->default(null)->change();
            $table->integer('cash_variance')->nullable()->default(null)->change();
        });

        // Shift tertutup dengan kas fisik 0 padahal kas seharusnya > 0 = tidak pernah dihitung.
        DB::table('shifts')
            ->where('status', 'closed')
            ->where('counted_cash', 0)
            ->where('expected_cash', '>', 0)
            ->update(['counted_cash' => null, 'cash_variance' => null]);

        // Shift yang masih berjalan belum punya rekonsiliasi.
        DB::table('shifts')
            ->where('status', 'open')
            ->update(['counted_cash' => null, 'cash_variance' => null]);
    }

    public function down(): void
    {
        DB::table('shifts')->whereNull('counted_cash')->update(['counted_cash' => 0]);
        DB::table('shifts')->whereNull('cash_variance')->update([
            'cash_variance' => DB::raw('counted_cash - expected_cash'),
        ]);

        Schema::table('shifts', function (Blueprint $table) {
            $table->unsignedInteger('counted_cash')->default(0)->change();
            $table->integer('cash_variance')->default(0)->change();
        });
    }
};
