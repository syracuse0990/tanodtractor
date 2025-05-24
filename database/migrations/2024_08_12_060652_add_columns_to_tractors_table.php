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
        Schema::table('tractors', function (Blueprint $table) {
            $table->string('first_maintenance_hr')->nullable()->after('maintenance_kilometer');
            $table->string('imei')->nullable()->after('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            $table->dropColumn('first_maintenance_hr');
            $table->dropColumn('imei');
        });
    }
};
