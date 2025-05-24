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
            $table->string('mc_type')->nullable()->after('expiration_date');
            $table->string('mc_type_use_scope')->nullable()->after('mc_type');
            $table->string('sim')->nullable()->after('mc_type_use_scope');
            $table->string('activation_time')->nullable()->after('sim');
            $table->string('remark')->nullable()->after('activation_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('mc_type');
            $table->dropColumn('mc_type_use_scope');
            $table->dropColumn('sim');
            $table->dropColumn('activation_time');
            $table->dropColumn('remark');
        });
    }
};
