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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable'); // Polymorphic relationship
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('type')->comment('image, video, document, etc.');
            $table->string('disk')->default('public');
            $table->string('path'); // Path on disk or URL
            $table->string('provider')->default('local')->comment('local, vimeo, youtube, etc.');
            $table->json('metadata')->nullable()->comment('Width, height, duration, etc.');
            $table->integer('size')->nullable()->comment('File size in bytes');
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Add index
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
