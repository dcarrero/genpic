<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // I5: Índice único en stripe_checkout_id
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('stripe_checkout_id');
        });

        // I5b: Índices compuestos para jobs de email (evitar full table scans)
        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->index(['purged_at', 'created_at', 'user_id', 'abandonment_email_count'], 'gs_abandonment_idx');
            $table->index(['purged_at', 'created_at', 'user_id', 'upgrade_email_count'], 'gs_upgrade_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_id']);
        });

        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->dropIndex('gs_abandonment_idx');
            $table->dropIndex('gs_upgrade_idx');
        });
    }
};
