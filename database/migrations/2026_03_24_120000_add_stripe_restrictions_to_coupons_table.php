<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('first_time_only')->default(false)->after('once_per_email');
            $table->unsignedInteger('minimum_amount_cents')->nullable()->after('first_time_only');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['first_time_only', 'minimum_amount_cents']);
        });
    }
};
