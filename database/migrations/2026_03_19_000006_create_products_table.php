<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_key', 50)->unique();
            $table->string('name_es', 150);
            $table->string('name_en', 150);
            $table->text('description_es')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedInteger('price_es_cents');
            $table->unsignedInteger('price_en_cents');
            $table->string('currency_es', 3)->default('EUR');
            $table->string('currency_en', 3)->default('USD');
            $table->string('stripe_price_id_es', 100)->nullable();
            $table->string('stripe_price_id_en', 100)->nullable();
            $table->enum('type', ['digital', 'physical'])->default('digital');
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('highlight')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });

        Schema::create('product_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('format_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['product_id', 'format_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_formats');
        Schema::dropIfExists('products');
    }
};
