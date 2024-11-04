<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmacyGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharmacy_group', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
			$table->softDeletes();
        });


        Schema::table('accounts', function (Blueprint $table) {
			$table->integer('pharmacy_group_id')->default(0)->after('class_id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pharmacy_group');
    }
}
