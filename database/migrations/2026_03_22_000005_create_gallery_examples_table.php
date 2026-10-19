<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_examples', function (Blueprint $table) {
            $table->id();
            $table->string('title_es', 100)->nullable();
            $table->string('title_en', 100)->nullable();
            $table->enum('photo_type', ['family', 'couple', 'individual', 'group'])->default('family');
            $table->foreignId('artistic_style_id')->constrained()->cascadeOnDelete();
            $table->string('style_key', 50);

            // Paths de imágenes (en storage local o Wasabi)
            $table->string('original_path', 500);    // Foto original de stock
            $table->string('result_path', 500);       // Resultado generado con IA
            $table->string('thumbnail_path', 500)->nullable(); // Thumb 400px para grid

            $table->boolean('featured')->default(false); // Mostrar en landing
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['active', 'featured', 'sort_order']);
            $table->index('photo_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_examples');
    }
};
