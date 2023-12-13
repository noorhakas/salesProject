<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            //
			$table->tinyInteger('status')->default(0)->after('type')->command('0-->pending,1-->accept ,2-->reject ,3-->completed');
            $table->integer('approved_or_rejected_by')->default(0)->after('status');
		});

		 Schema::create('plan_status', function (Blueprint $table) {
            $table->id();
			$table->integer('plan_id');
			$table->tinyInteger('status')->default(0)->command('0-->pending,1-->accept ,2-->reject ,3-->completed');
			$table->integer('approved_or_rejected_by');
			$table->text('note')->nullable();
            $table->timestamps();
        });
       

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            //
        });
    }
}
