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
            // Add back map_url and phone_number to events
            $table->string('map_url')->nullable()->after('event_location_id');
            $table->string('phone_number')->nullable()->after('map_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Remove map_url and phone_number
            $table->dropColumn(['map_url', 'phone_number']);
        });
    }
};
