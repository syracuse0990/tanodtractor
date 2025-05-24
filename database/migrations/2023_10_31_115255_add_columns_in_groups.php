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
        Schema::table('tractor_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('tractor_groups', 'farmer_ids')) {
                $table->string('farmer_ids')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tractor_groups', 'tractor_ids')) {
                $table->string('tractor_ids')->nullable()->after('farmer_ids');
            }
            if (!Schema::hasColumn('tractor_groups', 'device_ids')) {
                $table->string('device_ids')->nullable()->after('tractor_ids');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tractor_groups', function (Blueprint $table) {
            if (Schema::hasColumn('tractor_groups', 'farmer_ids')) {
                $table->dropColumn('farmer_ids');
            }
            if (Schema::hasColumn('tractor_groups', 'tractor_ids')) {
                $table->dropColumn('tractor_ids');
            }
            if (Schema::hasColumn('tractor_groups', 'tractor_ids')) {
                $table->dropColumn('device_ids');
            }
        });
    }
};
