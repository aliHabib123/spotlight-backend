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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('kids_price', 10, 2)->nullable();
            $table->decimal('infant_price', 10, 2)->nullable();
            $table->integer('capacity')->nullable();
            $table->foreignId('tour_location_id')->constrained();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(false)->comment('Tours require approval by admin');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
