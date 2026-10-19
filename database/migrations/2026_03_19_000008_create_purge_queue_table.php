<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purge_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_session_id')->unique()->constrained();
            $table->json('paths_to_purge');
            $table->unsignedInteger('estimated_bytes')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'error'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('bytes_freed')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purge_queue');
    }
};
