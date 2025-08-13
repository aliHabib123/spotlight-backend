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
        Schema::create('spotlight_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spotlight_id')->constrained()->cascadeOnDelete();
            $table->integer('rating')->comment('Rating from 1-5');
            $table->text('comment')->nullable();
            $table->timestamps();
            
            // Ensure a user can only rate a spotlight once
            $table->unique(['user_id', 'spotlight_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_ratings');
    }
};
