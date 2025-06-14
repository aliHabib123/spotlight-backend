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
        Schema::create('spotlight_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spotlight_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_definition_id')->constrained('spotlight_attribute_definitions')->onDelete('cascade');
            $table->foreignId('attribute_option_id')->nullable()->constrained('spotlight_attribute_options')->onDelete('cascade')->comment('For enum types');
            $table->string('value')->nullable()->comment('For string, number, boolean types');
            $table->timestamps();
            
            // Add indexes for efficient filtering
            $table->index(['spotlight_id', 'attribute_definition_id'], 'spot_attr_idx');
            $table->index(['attribute_definition_id', 'attribute_option_id'], 'attr_opt_idx');
            $table->index(['attribute_definition_id', 'value'], 'attr_val_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_attribute_values');
    }
};
