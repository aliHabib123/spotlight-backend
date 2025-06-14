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
        Schema::create('spotlight_attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->comment('string, number, boolean, enum');
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable()->comment('Laravel validation rules in JSON format');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true)->comment('Whether this attribute can be used as a filter');
            $table->boolean('allows_multiple')->default(false)->comment('Whether multiple values can be selected');
            $table->string('display_type')->nullable()->comment('How to display in forms: select, radio, checkbox, etc.');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Add indexes
            $table->index('slug');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotlight_attribute_definitions');
    }
};
