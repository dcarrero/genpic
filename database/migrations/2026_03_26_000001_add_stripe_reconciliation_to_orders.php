<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Importes reales de Stripe (reconciliación post-pago)
            $table->unsignedInteger('stripe_amount_total')->nullable()->after('stripe_customer_id');
            $table->unsignedInteger('stripe_amount_discount')->nullable()->after('stripe_amount_total');
            $table->unsignedInteger('stripe_amount_tax')->nullable()->after('stripe_amount_discount');
            $table->string('stripe_currency', 3)->nullable()->after('stripe_amount_tax');
            $table->string('stripe_promotion_code', 100)->nullable()->after('stripe_currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_amount_total',
                'stripe_amount_discount',
                'stripe_amount_tax',
                'stripe_currency',
                'stripe_promotion_code',
            ]);
        });
    }
};
