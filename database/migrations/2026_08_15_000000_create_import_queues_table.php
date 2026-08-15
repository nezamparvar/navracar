<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_queues', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['dubizzle', 'dubicars', 'yallamotor'])->index();
            $table->string('source_listing_id')->nullable()->index();
            $table->string('source_url');
            $table->enum('source_method', ['browser_extension', 'direct_url', 'manual_html'])->default('browser_extension');
            $table->enum('status', ['captured', 'parsing', 'needs_review', 'images_pending', 'ready', 'failed', 'published'])->default('captured')->index();
            $table->foreignId('car_listing_id')->nullable()->constrained('car_listings')->nullOnDelete();
            $table->json('captured_data')->nullable();
            $table->json('parsed_data')->nullable();
            $table->json('diagnostics')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->float('parse_confidence')->default(0)->nullable();
            $table->integer('image_count')->default(0);
            $table->integer('images_imported')->default(0);
            $table->string('canonical_url')->nullable();
            $table->string('duplicate_detected_with')->nullable()->comment('If not null, value is the slug of existing car_listing');
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();

            $table->index('created_at');
            $table->index(['source', 'source_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_queues');
    }
};
