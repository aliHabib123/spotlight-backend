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
        Schema::table('spotlight_attribute_options', function (Blueprint $table) {
            $table->foreignId('parent_option_id')->nullable()->after('id')
                ->references('id')->on('spotlight_attribute_options')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spotlight_attribute_options', function (Blueprint $table) {
            $table->dropForeign(['parent_option_id']);
            $table->dropColumn('parent_option_id');
        });
    }
};
