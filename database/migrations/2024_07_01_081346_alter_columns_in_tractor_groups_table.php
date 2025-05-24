<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tractor_groups', function (Blueprint $table) {
            // Change columns to nullable text
            $table->text('farmer_ids')->nullable()->change();
            $table->text('tractor_ids')->nullable()->change();
            $table->text('device_ids')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('tractor_groups', function (Blueprint $table) {
            // Change columns back to nullable string
            $table->string('farmer_ids')->nullable()->change();
            $table->string('tractor_ids')->nullable()->change();
            $table->string('device_ids')->nullable()->change();
        });
    }
};
