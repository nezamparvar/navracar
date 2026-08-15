<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('source', 32);
            $table->text('source_url');
            $table->string('status', 32)->index();
            $table->json('payload_json')->nullable();
            $table->json('parsed_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('import_queue'); }
};

