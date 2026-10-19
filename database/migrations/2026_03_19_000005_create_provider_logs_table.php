<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generation_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('model', 100)->nullable();
            $table->enum('status', ['success', 'timeout', 'error', 'skipped']);
            $table->unsignedInteger('elapsed_ms')->nullable();
            $table->decimal('cost_usd', 8, 4)->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['provider', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_logs');
    }
};
