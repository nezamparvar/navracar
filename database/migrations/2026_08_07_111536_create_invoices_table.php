<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('quote_requests')->nullOnDelete();
            $table->string('invoice_number', 50)->unique()->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 64)->nullable();
            $table->string('customer_address', 500)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('car_label')->nullable();
            $table->string('category', 100)->nullable();
            $table->longText('breakdown_json')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('toman');
            $table->decimal('exchange_rate', 14, 2)->nullable();
            $table->date('valid_until')->nullable();
            $table->string('payment_terms', 500)->nullable();
            $table->string('invoice_type', 30)->default('full');
            $table->string('status', 30)->default('پیش‌نویس');
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
