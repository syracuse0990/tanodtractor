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
        Schema::create('assigned_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index()->nullable();
            $table->integer('group_id')->index()->nullable();
            $table->integer('type_id')->index()->default(0);
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
        Schema::dropIfExists('assigned_groups');
    }
};
