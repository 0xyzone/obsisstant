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
            $table->string('username')->unique()->after('name');
            $table->string('email')->nullable()->change();
            $table->string('contact_number')->nullable()->unique()->after('email');
            $table->string('current_address', 500)->nullable()->after('contact_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['contact_number']);
            $table->dropColumn(['username', 'contact_number', 'current_address']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
