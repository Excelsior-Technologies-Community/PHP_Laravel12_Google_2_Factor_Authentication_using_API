<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_recovery_codes')->nullable()->after('google2fa_enabled');
            $table->timestamp('recovery_codes_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('pending_email')->nullable()->after('email');
            $table->string('pending_email_token')->nullable()->after('pending_email');
            $table->timestamp('pending_email_expires_at')->nullable()->after('pending_email_token');
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_recovery_codes',
                'recovery_codes_confirmed_at',
                'pending_email',
                'pending_email_token',
                'pending_email_expires_at',
                'password_changed_at',
            ]);
        });
    }
};
