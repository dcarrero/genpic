<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos upsell en products
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_upsell_addon')->default(false)->after('active');
            $table->foreignId('upsell_for_product_id')->nullable()->after('is_upsell_addon')
                  ->constrained('products')->nullOnDelete();
            $table->unsignedTinyInteger('max_quantity')->default(1)->after('upsell_for_product_id');
        });

        // Campos upsell en order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_upsell')->default(false)->after('total_cents');
            $table->foreignId('artistic_style_id')->nullable()->after('is_upsell')
                  ->constrained()->nullOnDelete();
            $table->enum('generation_status', ['pending', 'processing', 'completed', 'failed'])
                  ->nullable()->after('artistic_style_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['artistic_style_id']);
            $table->dropColumn(['is_upsell', 'artistic_style_id', 'generation_status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['upsell_for_product_id']);
            $table->dropColumn(['is_upsell_addon', 'upsell_for_product_id', 'max_quantity']);
        });
    }
};
