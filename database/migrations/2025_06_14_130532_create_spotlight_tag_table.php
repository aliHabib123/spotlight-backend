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
        Schema::create('spotlight_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spotlight_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Ensure each tag is only assigned once per spotlight
            $table->unique(['spotlight_id', 'tag_id']);
            
            // Add index for efficient lookups
            $table->index(['tag_id', 'spotlight_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_tag');
    }
};
