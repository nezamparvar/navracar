<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_installations', function (Blueprint $table) {
            $table->id();
            $table->uuid('installation_id')->unique();
            $table->char('secret_hash', 64);
            $table->foreignId('mobile_customer_id')->nullable()->constrained('mobile_customers')->nullOnDelete();
            $table->boolean('analytics_consent')->default(false);
            $table->boolean('notifications_consent')->default(false);
            $table->string('device_manufacturer', 80)->nullable();
            $table->string('device_model', 120)->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('os_version', 40)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('acquisition_source', 80)->nullable();
            $table->string('acquisition_campaign', 120)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('push_token')->nullable();
            $table->char('push_token_hash', 64)->nullable()->unique();
            $table->timestamp('push_token_updated_at')->nullable();
            $table->timestamps();

            $table->index(['analytics_consent', 'last_seen_at']);
            $table->index(['country', 'city']);
            $table->index('app_version');
        });

        Schema::create('mobile_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_app_installation_id')->constrained('mobile_app_installations')->cascadeOnDelete();
            $table->foreignId('mobile_customer_id')->nullable()->constrained('mobile_customers')->nullOnDelete();
            $table->string('name', 50);
            $table->string('page', 100)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['name', 'occurred_at']);
            $table->index(['mobile_app_installation_id', 'occurred_at'], 'mobile_events_installation_time_index');
            $table->index(['mobile_customer_id', 'occurred_at'], 'mobile_events_customer_time_index');
        });

        Schema::create('mobile_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->json('data')->nullable();
            $table->json('segment')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('targeted_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('disabled_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_push_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_push_notification_id')->constrained('mobile_push_notifications')->cascadeOnDelete();
            $table->foreignId('mobile_app_installation_id')->constrained('mobile_app_installations')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('error_code', 120)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->unique(['mobile_push_notification_id', 'mobile_app_installation_id'], 'mobile_push_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_push_deliveries');
        Schema::dropIfExists('mobile_push_notifications');
        Schema::dropIfExists('mobile_analytics_events');
        Schema::dropIfExists('mobile_app_installations');
    }
};
