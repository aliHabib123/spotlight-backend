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
        Schema::table("spotlight_categories", function (Blueprint $table) {
            $table->foreignId("home_screen_location_id")->nullable()->constrained("home_screen_locations")->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("spotlight_categories", function (Blueprint $table) {
            $table->dropForeign(["home_screen_location_id"]);
            $table->dropColumn("home_screen_location_id");
        });
    }
};
