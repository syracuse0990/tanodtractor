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
        Schema::table('device_geo_fences', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('geo_fence_id');
            $table->string('longitude')->nullable()->after('latitude');
            $table->string('radius')->nullable()->after('longitude');
            $table->string('fence_name')->nullable()->after('radius');
            $table->string('zoom_level')->nullable()->after('fence_name');
            $table->date('date')->nullable()->after('zoom_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_geo_fences', function (Blueprint $table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('radius');
            $table->dropColumn('fence_name');
            $table->dropColumn('zoom_level');
            $table->dropColumn('date');
        });
    }
};
