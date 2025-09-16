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
        Schema::table('spotlights', function (Blueprint $table) {
            $table->string('thumbnail_1200x360')->nullable()->after('featured_image')->comment('Thumbnail image 1200x360 for landscape display');
            $table->string('thumbnail_1080x1080')->nullable()->after('thumbnail_1200x360')->comment('Thumbnail image 1080x1080 for square display');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotlights', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_1200x360', 'thumbnail_1080x1080']);
        });
    }
};
