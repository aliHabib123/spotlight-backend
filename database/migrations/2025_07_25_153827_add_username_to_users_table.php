<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add the username column (nullable at first)
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
        });

        // Step 2: Generate unique usernames for existing users
        $users = User::all();
        foreach ($users as $user) {
            // Create a username based on the name, or generate a random one if name is not available
            $baseUsername = $user->name ? Str::slug($user->name) : 'user';
            $username = $baseUsername;
            $counter = 1;
            
            // Make sure the username is unique
            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            $user->username = $username;
            $user->save();
        }

        // Step 3: Add unique constraint and make not nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
