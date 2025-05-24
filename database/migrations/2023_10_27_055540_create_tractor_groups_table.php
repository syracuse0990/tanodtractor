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
        Schema::create('tractor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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
        Schema::dropIfExists('tractor_groups');
    }
};
