<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('total_base_owed')->default(0); // dibayar ke vendor
            $table->unsignedInteger('total_margin')->default(0);    // keuntungan owner
            $table->unsignedInteger('total_gross')->default(0);     // total uang masuk
            $table->unsignedInteger('order_count')->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'vendor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
