<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir nuevas columnas JSON
        Schema::table('email_sequences', function (Blueprint $table) {
            $table->json('subject')->nullable()->after('discount_product_key');
            $table->json('heading')->nullable()->after('subject');
            $table->json('body')->nullable()->after('heading');
            $table->json('cta_text')->nullable()->after('body');
            $table->json('coupon_text')->nullable()->after('cta_text');
        });

        // 2. Migrar datos existentes de subject_es/subject_en a JSON
        $sequences = DB::table('email_sequences')->get();
        foreach ($sequences as $seq) {
            DB::table('email_sequences')->where('id', $seq->id)->update([
                'subject' => json_encode([
                    'es' => $seq->subject_es,
                    'en' => $seq->subject_en,
                ]),
            ]);
        }

        // 3. Borrar columnas viejas
        Schema::table('email_sequences', function (Blueprint $table) {
            $table->dropColumn(['subject_es', 'subject_en', 'template']);
        });
    }

    public function down(): void
    {
        Schema::table('email_sequences', function (Blueprint $table) {
            $table->string('subject_es')->nullable();
            $table->string('subject_en')->nullable();
            $table->string('template', 100)->nullable();
            $table->dropColumn(['subject', 'heading', 'body', 'cta_text', 'coupon_text']);
        });
    }
};
