<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->json('quote');      // {"es": "...", "en": "...", "pt": "..."}
            $table->json('author');     // {"es": "María G.", "en": "María G."}
            $table->json('tag');        // {"es": "Familia", "en": "Family"}
            $table->unsignedTinyInteger('rating')->default(5); // 1-5 estrellas
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
