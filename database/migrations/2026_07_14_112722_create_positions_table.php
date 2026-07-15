<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('ps_key');
            $table->string('name');
            $table->bigInteger('parent_id');
            $table->timestamps();
        });


        Schema::create('public_holiday', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('active');
            $table->timestamps();
            $table->softDeletes();
        });

         Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->time('time_from');
            $table->time('time_to');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('positions');
    }
}
