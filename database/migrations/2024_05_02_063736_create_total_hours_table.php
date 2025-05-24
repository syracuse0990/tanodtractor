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
        Schema::create('total_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('tractor_id')->index()->nullable();
            $table->string('hours')->nullable();
            $table->integer('user_id')->index()->nullable();
            $table->integer('type_id')->index()->nullable();
            $table->integer('state_id')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('total_hours');
    }
};
