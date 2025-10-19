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
        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('selected_date');
            $table->unsignedInteger('adults');
            $table->unsignedInteger('kids')->default(0);
            $table->unsignedInteger('infants')->default(0);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'canceled'])->default('pending');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50);
            $table->text('special_requests')->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'selected_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_bookings');
    }
};
