<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('code'); // e.g. BB / JD / MNL
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('payout_account')->nullable(); // rekening untuk transfer settlement
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
