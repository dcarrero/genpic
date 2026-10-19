<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // S10: unique constraint en email_sequences
        Schema::table('email_sequences', function (Blueprint $table) {
            $table->unique(['sequence_type', 'email_number']);
        });

        // Índice compuesto para queries frecuentes en jobs de abandonment/upgrade
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['generation_session_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('email_sequences', function (Blueprint $table) {
            $table->dropUnique(['sequence_type', 'email_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['generation_session_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
        });
    }
};
