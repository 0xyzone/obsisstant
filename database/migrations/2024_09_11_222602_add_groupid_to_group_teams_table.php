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
        Schema::table('group_teams', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete()->after('team_id');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_teams', function (Blueprint $table) {
            //
        });
    }
};
