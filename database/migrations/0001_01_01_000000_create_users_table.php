<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('locale', 10)->default('es');
            $table->boolean('interested_in_print')->default(false);
            $table->string('stripe_customer_id', 100)->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('stripe_customer_id');
        });

        // No sessions ni password_reset — acceso solo via signed URLs
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
