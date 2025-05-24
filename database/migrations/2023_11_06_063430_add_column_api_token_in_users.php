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
            if (!Schema::hasColumn('users', 'api_access_token')) {
                $table->string('api_access_token')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'api_token_time')) {
                $table->dateTime('api_token_time')->nullable()->after('api_access_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'api_access_token')) {
                $table->dropColumn('api_access_token');
            }
            if (Schema::hasColumn('users', 'api_token_time')) {
                $table->dropColumn('api_token_time');
            }
        });
    }
};
