<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('browser_extension_pairings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('pairing_code', 6)->unique();
            $table->string('token', 64)->unique();
            $table->string('device_name')->default('Browser Extension');
            $table->enum('environment', ['staging', 'production'])->default('staging');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index('environment');
            $table->index('token');
            $table->index(['admin_user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_extension_pairings');
    }
};
