<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('relay_token', 80)->nullable()->unique()->after('remember_token');
        });

        Schema::table('obs_studios', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });

        Schema::table('obs_screens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });

        Schema::table('obs_macros', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });

        // Backfill relay tokens for existing users and bind unowned records to the earliest registered user
        $users = DB::table('users')->select('id', 'relay_token')->orderBy('id')->get();
        foreach ($users as $existingUser) {
            if (! $existingUser->relay_token) {
                DB::table('users')
                    ->where('id', $existingUser->id)
                    ->update(['relay_token' => 'usr_'.Str::random(48)]);
            }
        }

        $primaryUserId = $users->first()?->id;
        if ($primaryUserId !== null) {
            DB::table('obs_studios')->whereNull('user_id')->update(['user_id' => $primaryUserId]);
            DB::table('obs_screens')->whereNull('user_id')->update(['user_id' => $primaryUserId]);
            DB::table('obs_macros')->whereNull('user_id')->update(['user_id' => $primaryUserId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obs_macros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('obs_screens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('obs_studios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['relay_token']);
            $table->dropColumn('relay_token');
        });
    }
};
