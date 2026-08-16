<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_queue', function (Blueprint $table) {
            $table->string('source_platform', 32)->nullable()->after('source');
            $table->string('capture_method', 32)->nullable()->after('source_platform');
        });
    }

    public function down(): void
    {
        Schema::table('import_queue', function (Blueprint $table) {
            $table->dropColumn(['source_platform', 'capture_method']);
        });
    }
};
