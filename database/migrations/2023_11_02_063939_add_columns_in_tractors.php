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
            if (!Schema::hasColumn('tractors', 'max_speed')) {
                $table->double('max_speed', 32)->nullable()->after('installation_address');
            }
            if (!Schema::hasColumn('tractors', 'maintenance_kilometer')) {
                $table->double('maintenance_kilometer')->nullable()->after('max_speed');
            }
            if (!Schema::hasColumn('tractors', 'chasis_no')) {
                $table->string('chasis_no')->nullable()->after('maintenance_kilometer');
            }
            if (!Schema::hasColumn('tractors', 'insurance_effect_date')) {
                $table->string('insurance_effect_date')->nullable()->after('chasis_no');
            }
            if (!Schema::hasColumn('tractors', 'insurance_expire_date')) {
                $table->string('insurance_expire_date')->nullable()->after('insurance_effect_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            if (Schema::hasColumn('tractors', 'max_speed')) {
                $table->dropColumn('max_speed');
            }
            if (Schema::hasColumn('tractors', 'maintenance_kilometer')) {
                $table->dropColumn('maintenance_kilometer');
            }
            if (Schema::hasColumn('tractors', 'chasis_no')) {
                $table->dropColumn('chasis_no');
            }
            if (Schema::hasColumn('tractors', 'insurance_effect_date')) {
                $table->dropColumn('insurance_effect_date');
            }
            if (Schema::hasColumn('tractors', 'insurance_expire_date')) {
                $table->dropColumn('insurance_expire_date');
            }
        });
    }
};
