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
            $table->string('phone_country', 64)->nullable()->after('email');
            $table->string('country_code')->nullable()->after('phone_country');
            $table->timestamp('phone_verified_at')->nullable()->after('profile_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_country');
            $table->dropColumn('country_code');
            $table->dropColumn('phone_verified_at');
        });
    }
};
