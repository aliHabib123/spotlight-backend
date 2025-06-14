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
        Schema::create('spotlight_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained('spotlight_attribute_definitions')->onDelete('cascade');
            $table->string('value');
            $table->string('label')->nullable()->comment('Display label if different from value');
            $table->string('color')->nullable()->comment('Hex color code for display');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Ensure unique values per attribute
            $table->unique(['attribute_definition_id', 'value']);
            
            // Index for lookups
            $table->index('attribute_definition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_attribute_options');
    }
};
