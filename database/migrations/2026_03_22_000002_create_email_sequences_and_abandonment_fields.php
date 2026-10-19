<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de secuencias de email configurables desde admin
        Schema::create('email_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_key', 50)->unique(); // abandonment_1, upgrade_2, etc.
            $table->enum('sequence_type', ['abandonment', 'upgrade'])->default('abandonment');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);

            $table->unsignedSmallInteger('delay_hours'); // horas desde creación de sesión
            $table->unsignedTinyInteger('email_number'); // 1, 2, 3, 4...

            $table->unsignedTinyInteger('discount_percent')->default(0); // 0 = sin descuento
            $table->string('discount_product_key', 50)->nullable(); // producto al que aplica el descuento

            $table->string('subject_es');
            $table->string('subject_en');
            $table->string('template', 100); // nombre del blade template

            $table->timestamps();

            $table->index(['sequence_type', 'active', 'email_number']);
        });

        // Campos de tracking en generation_sessions
        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('abandonment_email_count')->default(0)->after('expiry_warning_sent_at');
            $table->timestamp('abandonment_last_sent_at')->nullable()->after('abandonment_email_count');
            $table->unsignedTinyInteger('upgrade_email_count')->default(0)->after('abandonment_last_sent_at');
            $table->timestamp('upgrade_last_sent_at')->nullable()->after('upgrade_email_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequences');

        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'abandonment_email_count', 'abandonment_last_sent_at',
                'upgrade_email_count', 'upgrade_last_sent_at',
            ]);
        });
    }
};
