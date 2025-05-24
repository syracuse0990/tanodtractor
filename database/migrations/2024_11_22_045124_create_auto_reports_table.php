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
        Schema::create('auto_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name')->nullable();
            $table->text('device_ids')->nullable();
            $table->integer('frequency')->index()->nullable();
            $table->string('email_addresses')->nullable();
            $table->integer('from_day')->index()->nullable();
            $table->string('from_time')->nullable();
            $table->integer('to_day')->index()->nullable();
            $table->string('to_time')->nullable();
            $table->integer('execution_day')->index()->nullable();
            $table->string('execution_time')->nullable();
            $table->timestamps();
            $table->integer('created_by')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_reports');
    }
};
