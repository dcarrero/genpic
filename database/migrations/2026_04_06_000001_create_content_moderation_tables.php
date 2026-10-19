<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_violations', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('email', 255)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('file_hash', 64)->index(); // SHA-256
            $table->string('file_name', 255)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->string('violation_type', 50); // nudity, weapons, drugs, violence, hate, content_policy, other
            $table->decimal('violation_score', 5, 4)->nullable(); // 0.0000 - 1.0000
            $table->string('provider', 50); // sightengine, fal_ai, openrouter, etc.
            $table->json('provider_response')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('ip_bans', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 255); // content_violation, manual
            $table->foreignId('violation_id')->nullable()->constrained('content_violations')->nullOnDelete();
            $table->timestamp('banned_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('unbanned_at')->nullable();
            $table->string('unbanned_by', 255)->nullable(); // admin email
            $table->timestamps();

            $table->index(['ip_address', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_bans');
        Schema::dropIfExists('content_violations');
    }
};
