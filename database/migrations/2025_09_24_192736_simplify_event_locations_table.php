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
        Schema::table('event_locations', function (Blueprint $table) {
            // Remove unnecessary fields, keep only name and slug
            $table->dropColumn([
                'address',
                'city',
                'country',
                'latitude',
                'longitude',
                'map_url',
                'is_active'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_locations', function (Blueprint $table) {
            // Restore the dropped fields
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('map_url')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }
};
