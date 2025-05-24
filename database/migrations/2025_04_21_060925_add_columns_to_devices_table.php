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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('sim_iccid')->nullable()->after('sim');
            $table->string('sim_registration_code')->nullable()->after('sim_iccid');
            $table->string('mobile_data_load')->nullable()->after('sim_registration_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'sim_iccid',
                'sim_registration_code',
                'mobile_data_load'
            ]);
        });
    }
};
