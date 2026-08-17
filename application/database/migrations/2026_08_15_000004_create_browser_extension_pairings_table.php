<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('browser_extension_pairings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->char('pairing_code_hash', 64)->nullable()->unique();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->char('token_last_four', 4)->nullable();
            $table->string('device_name')->default('Browser Extension');
            $table->string('environment', 16)->default('staging');
            $table->string('status', 16)->default('pending');
            $table->dateTime('expires_at');
            $table->dateTime('paired_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['admin_user_id', 'status']);
            $table->index(['environment', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_extension_pairings');
    }
};
