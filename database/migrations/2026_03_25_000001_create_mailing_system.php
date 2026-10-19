<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos adicionales en users para importación
        Schema::table('users', function (Blueprint $table) {
            $table->string('import_source', 50)->nullable()->after('locale')
                ->comment('Origen: faceco, newsletter, etc.');
            $table->string('import_batch', 100)->nullable()->after('import_source')
                ->comment('Lote: faceco-2026-03-25');
            $table->string('first_name', 100)->nullable()->after('email');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->char('country_code', 2)->nullable()->after('locale');
            $table->string('timezone', 50)->nullable()->after('country_code');

            $table->index('import_source');
            $table->index('import_batch');
        });

        // Campañas de email
        Schema::create('mail_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->json('subject');        // {es: "...", en: "...", ...}
            $table->json('body');           // {es: "<html>...", en: "...", ...}
            $table->string('coupon_code', 50)->nullable();
            $table->string('target_source', 50)->nullable()
                ->comment('Filtrar por import_source. NULL = todos');
            $table->enum('status', ['draft', 'sending', 'paused', 'completed'])->default('draft');
            $table->unsignedInteger('batch_size')->default(100);
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('target_source');
        });

        // Registro de cada envío individual
        Schema::create('mail_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mail_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email', 255);
            $table->char('detected_language', 2)->default('en');
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_sends');
        Schema::dropIfExists('mail_campaigns');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['import_source']);
            $table->dropIndex(['import_batch']);
            $table->dropColumn(['import_source', 'import_batch', 'first_name', 'last_name', 'country_code', 'timezone']);
        });
    }
};
