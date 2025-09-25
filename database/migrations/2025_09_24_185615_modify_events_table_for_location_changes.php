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
        Schema::table('events', function (Blueprint $table) {
            // Remove published_at field
            $table->dropColumn('published_at');
            
            // Remove location field and add location_id foreign key
            $table->dropColumn('location');
            $table->foreignId('event_location_id')->nullable()->constrained('event_locations')->onDelete('set null');
            
            // Remove map_url since it's now in event_locations
            $table->dropColumn('map_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Restore published_at field
            $table->timestamp('published_at')->nullable();
            
            // Restore location and map_url fields
            $table->string('location');
            $table->string('map_url')->nullable();
            
            // Remove location_id foreign key
            $table->dropForeign(['event_location_id']);
            $table->dropColumn('event_location_id');
        });
    }
};
