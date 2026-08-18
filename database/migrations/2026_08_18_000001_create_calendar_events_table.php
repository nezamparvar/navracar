<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // follow_up_call | consultation_meeting | payment_call | delivery_meeting
            $table->string('title')->nullable();
            $table->foreignId('quote_request_id')->nullable()->constrained('quote_requests')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->string('status', 20)->default('scheduled'); // scheduled | completed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'starts_at']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
