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
        Schema::create('spotlight_category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('spotlight_categories')->onDelete('cascade');
            $table->foreignId('attribute_definition_id')->constrained('spotlight_attribute_definitions')->onDelete('cascade');
            $table->boolean('is_required')->nullable()->comment('Overrides attribute definition if set');
            $table->boolean('is_featured')->default(false)->comment('Highlighted in filtering UI');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Ensure each attribute is only assigned once per category
            $table->unique(['category_id', 'attribute_definition_id'], 'cat_attr_unique');
            
            // Index for lookups
            $table->index(['attribute_definition_id', 'category_id'], 'attr_cat_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_category_attributes');
    }
};
