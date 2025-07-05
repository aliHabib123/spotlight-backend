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
        Schema::table('spotlight_categories', function (Blueprint $table) {
            $table->boolean('show_location_filter')->default(true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotlight_categories', function (Blueprint $table) {
            $table->dropColumn('show_location_filter');
        });
    }
};
