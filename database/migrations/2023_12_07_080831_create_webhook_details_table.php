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
        Schema::create('webhook_details', function (Blueprint $table) {
            $table->id();
            $table->text('data')->nullable();
            $table->integer('state_id')->index()->default(1);
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
        Schema::dropIfExists('webhook_details');
    }
};
