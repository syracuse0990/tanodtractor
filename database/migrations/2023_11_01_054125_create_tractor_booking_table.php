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
        Schema::create('tractor_bookings', function (Blueprint $table) {
            $table->id();
            $table->integer('tractor_id')->index()->nullable();
            $table->integer('device_id')->index()->nullable();
            $table->integer('slot_id')->index()->nullable();
            $table->text('purpose')->nullable();
            $table->integer('state_id')->index()->default(0);
            $table->integer('type_id')->index()->default(0);
            $table->timestamps();
            $table->integer('created_by')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tractor_bookings');
    }
};
