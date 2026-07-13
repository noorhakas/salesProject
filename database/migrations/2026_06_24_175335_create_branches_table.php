<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

         Schema::create('user_branches', function (Blueprint $table) {

            $table->bigInteger('user_id');

            $table->bigInteger('branch_id');


            $table->unique(['user_id', 'branch_id']);
        });

        Schema::create('user_departments', function (Blueprint $table) {

            $table->bigInteger('user_id');

            $table->bigInteger('department_id');


            $table->unique(['user_id', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('branches');
    }
}
