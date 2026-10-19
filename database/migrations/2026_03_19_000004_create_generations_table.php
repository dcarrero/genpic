<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('generation_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artistic_style_id')->nullable()->constrained()->nullOnDelete();

            // Estado
            $table->enum('status', [
                'queued', 'processing', 'completed', 'watermarked', 'failed',
            ])->default('queued');

            // Proveedor
            $table->string('provider', 50)->nullable();
            $table->string('provider_job_id', 255)->nullable();
            $table->string('provider_model', 100)->nullable();
            $table->decimal('cost_usd', 8, 4)->nullable();

            // Archivos en Wasabi
            $table->string('raw_image_path', 500)->nullable();
            $table->string('watermarked_image_path', 500)->nullable();
            $table->unsignedInteger('raw_image_size_bytes')->nullable();

            // Prompt (auditoría)
            $table->text('prompt_used')->nullable();
            $table->text('negative_prompt_used')->nullable();
            $table->decimal('style_strength_used', 3, 2)->nullable();
            $table->bigInteger('seed')->nullable();

            // Tiempos
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('elapsed_ms')->nullable();

            // Error
            $table->text('error_message')->nullable();
            $table->tinyInteger('attempt_number')->unsigned()->default(1);

            $table->timestamps();

            $table->index('status');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
