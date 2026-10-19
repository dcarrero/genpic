<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('stripe_coupon_id', 100)->nullable();
            $table->string('stripe_promotion_code_id', 100)->nullable();

            $table->enum('type', ['percent', 'fixed'])->default('percent');
            $table->unsignedInteger('value'); // % o céntimos según type

            // null = todos los productos, array = product_keys específicos
            $table->json('product_keys')->nullable();

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->boolean('once_per_email')->default(true);

            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            $table->boolean('auto_generated')->default(false);
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
