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
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->date('day_date');
            $table->dateTime('action_time');
            $table->tinyInteger('action_type')->default(1);
            $table->enum('auth_method', [
            'fingerprint',
            'face',
            'manual',
            'biometric'
        ])->nullable();
            $table->tinyInteger('device_id')->default(0);

            $table->string('location_lat');
            $table->string('location_lng');
            $table->string('faceprint')->nullable();
            $table->string('fingerprint')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
