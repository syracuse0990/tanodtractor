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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('imei_no')->nullable();
            $table->string('device_modal')->unique();
            $table->string('device_name')->nullable();
            $table->string('sales_time')->nullable();
            $table->string('subscription_expiration')->nullable();
            $table->dateTime('expiration_date')->nullable();
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
        Schema::dropIfExists('devices');
    }
};
