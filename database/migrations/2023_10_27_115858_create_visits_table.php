<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
			$table->integer('user_id');
			$table->integer('customer_id');
			$table->tinyInteger('type')->default(0)->comment('0->planned,1->unplanned');
			$table->tinyInteger('status')->default(0)->comment('0->no_action,1->pending,2->confirmed,3->visits,4->holiday');
			$table->date('visit_date');
			$table->time('start_time');
			$table->time('end_time');
			$table->integer('confirmed_by')->default(0);
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
        Schema::dropIfExists('visits');
    }
}
