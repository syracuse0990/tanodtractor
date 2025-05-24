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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->integer('role_id')->index()->nullable();
            $table->integer('gender')->index()->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('email_verification_otp')->nullable();
            $table->integer('device_type')->default(1)->comment("0 IOS, 1 Android");
            $table->text('fcm_token')->nullable();
            $table->integer('state_id')->index()->default(0);
            $table->integer('type_id')->index()->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->integer('created_by')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
