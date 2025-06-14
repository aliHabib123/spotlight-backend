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
        Schema::create('spotlights', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->foreignId('category_id')->constrained('spotlight_categories')->onDelete('restrict');
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('contact_info')->nullable()->comment('Store phone, email, website, etc.');
            $table->json('social_links')->nullable()->comment('Store social media links');
            $table->json('opening_hours')->nullable()->comment('Store opening hours in structured format');
            $table->string('video_url')->nullable();
            $table->string('video_provider')->nullable()->comment('local, vimeo, youtube, etc.');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->integer('average_rating')->nullable();
            $table->integer('review_count')->default(0);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('slug');
            $table->index('category_id');
            $table->index('location_id');
            $table->index(['is_featured', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlights');
    }
};
