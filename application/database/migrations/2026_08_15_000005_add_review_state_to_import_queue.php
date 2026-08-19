<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_queue', function (Blueprint $table) {
            $table->foreignId('published_listing_id')->nullable()->constrained('car_listings')->nullOnDelete();
            $table->unsignedSmallInteger('images_imported')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('import_queue', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_listing_id');
            $table->dropColumn('images_imported');
        });
    }
};
