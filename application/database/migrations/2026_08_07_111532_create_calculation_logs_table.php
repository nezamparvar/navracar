<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->string('car_label')->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('real_price_aed', 14, 2)->default(0);
            $table->decimal('customs_price_aed', 14, 2)->default(0);
            $table->decimal('free_rate', 14, 2)->default(0);
            $table->decimal('customs_rate', 14, 2)->default(0);
            $table->decimal('sea_freight_aed', 14, 2)->default(0);
            $table->decimal('permits_aed', 14, 2)->default(0);
            $table->decimal('storage_toman', 16, 2)->default(0);
            $table->decimal('sum_customs', 18, 2)->default(0);
            $table->decimal('sum_plate', 18, 2)->default(0);
            $table->decimal('total_no_profit', 18, 2)->default(0);
            $table->decimal('service_profit', 18, 2)->default(0);
            $table->decimal('total_with_profit', 18, 2)->default(0);
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculation_logs');
    }
};
