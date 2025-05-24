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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->string('tech_iso_code')->nullable()->after('tech_email');
            $table->string('tech_phone_code')->nullable()->after('tech_iso_code');
            $table->string('farmer_iso_code')->nullable()->after('farmer_email');
            $table->string('farmer_phone_code')->nullable()->after('farmer_iso_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn('tech_iso_code');
            $table->dropColumn('tech_phone_code');
            $table->dropColumn('farmer_iso_code');
            $table->dropColumn('farmer_phone_code');
        });
    }
};
