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
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('device_token')->unique();

            $table->string('device_name')->nullable();

            $table->string('browser')->nullable();

            $table->string('platform')->nullable();

            $table->ipAddress('ip_address')->nullable();

            $table->timestamp('last_used_at')->nullable();

            $table->dateTime('expires_at');

            $table->timestamps();

            $table->index('device_token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};