<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // sqlite: ->change() recrea la tabla con el nuevo CHECK
            Schema::table('mail_sends', function (Blueprint $table) {
                $table->enum('status', ['pending', 'sending', 'sent', 'failed', 'bounced'])
                    ->default('pending')->change();
            });

            return;
        }

        // MariaDB/MySQL: MODIFY directo — preserva datos, solo amplía el enum
        DB::statement("ALTER TABLE mail_sends MODIFY COLUMN status ENUM('pending', 'sending', 'sent', 'failed', 'bounced') NOT NULL DEFAULT 'pending'");

        // Saneo defensivo: si algún UPDATE previo dejó valores vacíos (strict off), recuperarlos
        DB::statement("UPDATE mail_sends SET status = 'pending' WHERE status = '' OR status IS NULL");
    }

    public function down(): void
    {
        // Devolver posibles 'sending' a 'pending' antes de estrechar el enum
        DB::table('mail_sends')->where('status', 'sending')->update(['status' => 'pending']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('mail_sends', function (Blueprint $table) {
                $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])
                    ->default('pending')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE mail_sends MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'bounced') NOT NULL DEFAULT 'pending'");
    }
};
