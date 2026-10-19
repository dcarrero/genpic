<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('generation_session_id')->constrained();

            // Stripe
            $table->string('stripe_checkout_id', 255)->nullable();
            $table->string('stripe_payment_intent_id', 255)->nullable()->unique();
            $table->string('stripe_customer_id', 100)->nullable();

            // Importes (siempre en céntimos)
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('coupon_code', 50)->nullable();

            // Estado
            $table->enum('status', [
                'pending', 'paid', 'processing', 'ready', 'refunded', 'failed',
            ])->default('pending');

            // Descarga
            $table->boolean('download_ready')->default(false);
            $table->timestamp('download_email_sent_at')->nullable();

            // Físico (Fase 4)
            $table->string('gelato_order_id', 100)->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->timestamp('shipped_at')->nullable();

            // Analytics
            $table->string('ab_variant', 20)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('generation_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('quantity')->unsigned()->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('total_cents');
            $table->json('export_paths')->nullable();
            $table->boolean('exports_ready')->default(false);
            $table->unsignedInteger('exports_size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
