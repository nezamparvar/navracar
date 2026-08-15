<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('browser_extension_pairings', function (Blueprint $table) {
            $table->id();
            $table->string('pairing_code', 6)->unique();
            $table->string('extension_token')->nullable()->unique();
            $table->enum('environment', ['staging', 'production'])->default('staging');
            $table->enum('status', ['pending', 'active', 'revoked', 'expired'])->default('pending');
            $table->foreignId('created_by')->constrained('admin_users')->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('pairing_code');
            $table->index('extension_token');
            $table->index('status');
            $table->index('environment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_extension_pairings');
    }
};
