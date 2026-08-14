<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_listing_id')->constrained('car_listings')->cascadeOnDelete();
            $table->string('local_path', 500);
            $table->string('source_url', 1000)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_listing_images');
    }
};
