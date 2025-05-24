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
            $table->integer('first_alert')->index()->default(0)->after('insurance_expire_date');
            $table->string('last_alert_hours')->default(0)->after('first_alert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            $table->dropColumn('first_alert');
            $table->dropColumn('last_alert_hours');
        });
    }
};
