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
        Schema::table('users', function (Blueprint $table) {
            $table->string('media_storage_path', 500)->nullable()->after('relay_token');
        });

        Schema::table('obs_studios', function (Blueprint $table) {
            $table->string('media_storage_path', 500)->nullable()->after('obs_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obs_studios', function (Blueprint $table) {
            $table->dropColumn('media_storage_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('media_storage_path');
        });
    }
};
