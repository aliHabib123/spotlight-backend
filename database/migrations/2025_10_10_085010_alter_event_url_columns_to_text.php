<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal for changing column types
        DB::statement('ALTER TABLE `events` MODIFY `map_url` TEXT NULL');
        DB::statement("ALTER TABLE `events` MODIFY `booking_link` TEXT NULL COMMENT 'External booking URL for the event'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous definitions
        DB::statement('ALTER TABLE `events` MODIFY `map_url` VARCHAR(255) NULL');
        DB::statement("ALTER TABLE `events` MODIFY `booking_link` VARCHAR(500) NULL COMMENT 'External booking URL for the event'");
    }
};
