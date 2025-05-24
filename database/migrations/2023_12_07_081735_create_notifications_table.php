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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index()->nullable();
            $table->string('title')->nullable();
            $table->string('message')->nullable();
            $table->integer('tractor_id')->index()->nullable();
            $table->integer('device_id')->index()->nullable();
            $table->integer('booking_id')->index()->nullable();
            $table->integer('is_read')->index()->nullable();
            $table->integer('type_id')->index()->nullable();
            $table->timestamps();
            $table->integer('created_by')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
