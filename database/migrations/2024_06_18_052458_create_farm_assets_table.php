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
        Schema::create('farm_assets', function (Blueprint $table) {
            $table->id();
            $table->string('number_plate')->nullable();
            $table->string('mileage')->nullable();
            $table->integer('condition')->nullable();
            $table->integer('type_id')->index()->nullable();
            $table->integer('state_id')->index()->default(1);
            $table->timestamps();
            $table->integer('created_by')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_assets');
    }
};
