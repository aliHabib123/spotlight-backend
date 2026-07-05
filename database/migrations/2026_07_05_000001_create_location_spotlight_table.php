<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_spotlight', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spotlight_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure each location is only assigned once per spotlight
            $table->unique(['spotlight_id', 'location_id']);

            // Add index for efficient lookups
            $table->index(['location_id', 'spotlight_id']);
        });

        // Backfill the pivot from the existing single location_id column so that
        // every spotlight that already has a location keeps it as its first location.
        DB::table('spotlights')
            ->whereNotNull('location_id')
            ->orderBy('id')
            ->chunk(200, function ($spotlights) {
                $now = now();
                $rows = [];

                foreach ($spotlights as $spotlight) {
                    $rows[] = [
                        'spotlight_id' => $spotlight->id,
                        'location_id' => $spotlight->location_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($rows)) {
                    // insertOrIgnore guards against re-runs / duplicate pairs.
                    DB::table('location_spotlight')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_spotlight');
    }
};
