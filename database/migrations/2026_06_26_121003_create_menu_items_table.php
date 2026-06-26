<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            // Harga disimpan sebagai integer Rupiah (tanpa desimal).
            $table->unsignedInteger('base_price')->default(0);   // jatah vendor
            $table->unsignedInteger('margin')->default(0);       // jatah owner
            $table->unsignedInteger('selling_price')->default(0); // = base_price + margin
            $table->boolean('is_available')->default(true);
            $table->string('image_path')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
