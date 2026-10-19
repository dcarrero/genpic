<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style_key', 50);
            $table->foreignId('artistic_style_id')->nullable()->constrained()->nullOnDelete();

            // Foto original
            $table->string('original_photo_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedInteger('original_size_bytes')->nullable();
            $table->boolean('face_detected')->nullable();

            // Estado
            $table->enum('status', [
                'pending', 'processing', 'watermarked', 'paid', 'failed', 'expired',
            ])->default('pending');

            // Email capture
            $table->timestamp('email_captured_at')->nullable();
            $table->timestamp('session_email_sent_at')->nullable();
            $table->timestamp('email_recovery_sent_at')->nullable();
            $table->timestamp('expiry_warning_sent_at')->nullable();

            // TTL y purga
            $table->timestamp('expires_at');
            $table->timestamp('purge_scheduled_at')->nullable();
            $table->timestamp('purged_at')->nullable();

            // Analytics
            $table->string('ab_variant', 20)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('referrer', 500)->nullable();

            // Meta
            $table->string('ip_address', 45)->nullable();
            $table->string('locale', 10)->default('es');
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_sessions');
    }
};
