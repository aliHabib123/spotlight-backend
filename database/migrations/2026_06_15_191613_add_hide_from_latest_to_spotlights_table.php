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
            $table->boolean('hide_from_latest')->default(false)->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotlights', function (Blueprint $table) {
            $table->dropColumn('hide_from_latest');
        });
    }
};
