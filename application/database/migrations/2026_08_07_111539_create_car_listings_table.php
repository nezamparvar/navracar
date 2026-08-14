<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source_url', 1000);
            $table->string('source_site', 50)->default('dubizzle');
            $table->string('status', 20)->default('draft'); // draft | published
            $table->string('slug')->unique();

            $table->string('title_en', 500)->nullable();
            $table->string('title_fa', 500)->nullable();

            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('trim_level', 255)->nullable();
            $table->string('model_year', 10)->nullable();

            $table->decimal('price_aed', 14, 2)->default(0);
            $table->string('kilometers', 50)->nullable();

            $table->string('body_type', 100)->nullable();
            $table->string('fuel_type', 100)->nullable();
            $table->string('transmission_type', 100)->nullable();
            $table->string('regional_specs', 100)->nullable();
            $table->string('steering_side', 100)->nullable();
            $table->string('seller_type', 100)->nullable();
            $table->string('warranty', 50)->nullable();
            $table->string('exterior_color', 100)->nullable();
            $table->string('interior_color', 100)->nullable();
            $table->string('horsepower', 100)->nullable();
            $table->string('engine_capacity_cc', 100)->nullable();
            $table->string('no_of_cylinders', 20)->nullable();
            $table->string('doors', 50)->nullable();
            $table->string('seating_capacity', 50)->nullable();

            $table->string('category_id', 20)->default('c2000');

            $table->string('location_text', 255)->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('specs_json')->nullable();
            $table->string('posted_on_dubizzle', 100)->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->timestamp('published_at')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_listings');
    }
};
