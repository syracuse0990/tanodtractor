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
        Schema::create('allocation_details', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id')->index()->nullable();
            $table->integer('user_id')->index()->nullable();
            $table->integer('tractor_id')->index()->nullable();
            $table->integer('device_id')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocation_details');
    }
};
