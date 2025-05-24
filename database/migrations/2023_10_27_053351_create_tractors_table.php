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
        Schema::create('tractors', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id')->index()->nullable();
            $table->integer('device_id')->index()->nullable();
            $table->integer('group_id')->index()->nullable();
            $table->string('no_plate')->nullable();
            $table->string('id_no')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('fuel_consumption')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->dateTime('installation_time')->nullable();
            $table->string('installation_address')->nullable();
            $table->integer('state_id')->index()->default(0);
            $table->integer('type_id')->index()->default(0);
            $table->timestamps();
            $table->integer('created_by')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tractors');
    }
};
