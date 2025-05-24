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
            $table->string('dr_date')->nullable()->after('last_alert_hours');
            $table->string('actual_delivery_date')->nullable()->after('dr_date');
            $table->string('dr_no')->nullable()->after('actual_delivery_date');
            $table->string('front_loader_sn')->nullable()->after('dr_no');
            $table->string('rotary_tiller_sn')->nullable()->after('front_loader_sn');
            $table->string('rotating_disc_plow_sn')->nullable()->after('rotary_tiller_sn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            $table->dropColumn([
                'dr_date',
                'actual_delivery_date',
                'dr_no',
                'front_loader_sn',
                'rotary_tiller_sn',
                'rotating_disc_plow_sn'
            ]);
        });
    }
};
