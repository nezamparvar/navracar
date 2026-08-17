<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password_hash');
            $table->timestamps();
        });

        Schema::create('mobile_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_customer_id')->constrained('mobile_customers')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('name', 100)->default('Android');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('mobile_favorites', function (Blueprint $table) {
            $table->foreignId('mobile_customer_id')->constrained('mobile_customers')->cascadeOnDelete();
            $table->foreignId('car_listing_id')->constrained('car_listings')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['mobile_customer_id', 'car_listing_id']);
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('mobile_customer_id')->nullable()->after('created_by')
                ->constrained('mobile_customers')->nullOnDelete();
            $table->index(['mobile_customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mobile_customer_id');
        });
        Schema::dropIfExists('mobile_favorites');
        Schema::dropIfExists('mobile_access_tokens');
        Schema::dropIfExists('mobile_customers');
    }
};
