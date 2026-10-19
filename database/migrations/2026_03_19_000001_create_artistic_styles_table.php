<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artistic_styles', function (Blueprint $table) {
            $table->id();
            $table->string('style_key', 50)->unique();
            $table->string('label_es', 100);
            $table->string('label_en', 100);
            $table->text('description_es')->nullable();
            $table->text('description_en')->nullable();
            $table->text('base_prompt');
            $table->text('negative_prompt')->nullable();
            $table->decimal('style_strength', 3, 2)->default(0.75);
            $table->string('preview_image', 500)->nullable();
            $table->tinyInteger('phase')->unsigned()->default(1);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artistic_styles');
    }
};
