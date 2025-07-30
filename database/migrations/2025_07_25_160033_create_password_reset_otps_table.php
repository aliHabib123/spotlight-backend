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
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('identifier'); // Email or phone number
            $table->string('otp_hash');
            $table->string('reset_token')->nullable();
            $table->datetime('expires_at');
            $table->boolean('is_used')->default(false);
            $table->integer('attempt_count')->default(0);
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('identifier');
            $table->index('reset_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
