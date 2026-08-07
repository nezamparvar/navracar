<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->string('name')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('car_label')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('temperature', 10)->default('warm');
            $table->longText('breakdown_json')->nullable();
            $table->longText('totals_json')->nullable();
            $table->decimal('total_with_profit', 18, 2)->default(0);
            $table->boolean('email_sent')->default(false);
            $table->string('source', 50)->default('سایت');
            $table->string('budget_range', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('follow_up_status', 50)->default('باز');
            $table->foreignId('current_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $table->string('loss_reason')->nullable();
            $table->date('next_call_date')->nullable();
            $table->string('ip_address', 64)->nullable();

            $table->index('created_at');
            $table->index('next_call_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
