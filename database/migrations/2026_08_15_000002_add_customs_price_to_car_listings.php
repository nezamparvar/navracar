<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('car_listings', fn (Blueprint $table) => $table->decimal('customs_price_aed', 14, 2)->nullable()->after('price_aed')); }
    public function down(): void { Schema::table('car_listings', fn (Blueprint $table) => $table->dropColumn('customs_price_aed')); }
};

