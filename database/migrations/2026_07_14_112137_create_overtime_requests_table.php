<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOvertimeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_minutes');
            $table->integer('approved_minutes');
            $table->integer('status');
            $table->bigInteger('approved_by');
            $table->timestamp('approved_at');
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
        Schema::dropIfExists('overtime_requests');
    }
}
