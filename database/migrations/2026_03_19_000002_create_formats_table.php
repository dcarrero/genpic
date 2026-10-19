<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formats', function (Blueprint $table) {
            $table->id();
            $table->string('format_key', 50)->unique();
            $table->string('label_es', 100);
            $table->string('label_en', 100);
            $table->smallInteger('width_px')->unsigned()->nullable();
            $table->smallInteger('height_px')->unsigned()->nullable();
            $table->unsignedInteger('max_dimension_px')->nullable();
            $table->enum('file_type', ['jpg', 'png', 'tiff'])->default('jpg');
            $table->tinyInteger('quality')->unsigned()->default(85);
            $table->string('description_es', 255)->nullable();
            $table->string('description_en', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formats');
    }
};
