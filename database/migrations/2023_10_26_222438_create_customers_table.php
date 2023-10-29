<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
			$table->string('name');
			$table->string('image')->nullable();
			$table->integer('brick_id');
			$table->integer('acc_type_id');
			$table->integer('specialty_id');
			$table->integer('class_id');
			$table->bigInteger('phone');
			$table->bigInteger('phone1')->nullable();
			$table->text('address');
			$table->longText('brief');
			$table->string('lat');
			$table->string('lng');
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
        Schema::dropIfExists('customers');
    }
}
