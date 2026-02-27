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
        Schema::create('tractor_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('imei', 20)->index();
            $table->string('device_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tractor_shares');
    }
};
