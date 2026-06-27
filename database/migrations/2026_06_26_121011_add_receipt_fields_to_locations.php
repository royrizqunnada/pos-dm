<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('receipt_name')->nullable()->after('name'); // nama besar di header struk
            $table->string('phone')->nullable()->after('address');
            $table->string('instagram')->nullable()->after('phone');
            $table->string('receipt_footer')->nullable()->after('instagram');
        });

        // Isi default agar struk lokasi yang sudah ada tetap tampil seperti sebelumnya.
        DB::table('locations')->whereNull('receipt_name')->update([
            'receipt_name' => 'DM KULINER',
            'instagram' => '@dmkuliner.id',
        ]);

        DB::table('locations')
            ->where('address', 'Randudongkal, Pemalang')
            ->update(['address' => 'Jl. Lingkar Utara, Komplek Arjuna, Randudongkal']);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['receipt_name', 'phone', 'instagram', 'receipt_footer']);
        });
    }
};
