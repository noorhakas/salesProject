<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_logs', function (Blueprint $table) {
                $table->id();
				$table->nullableMorphs('loggable');
				$table->string('action')->nullable();
				$table->string('code')->nullable();
				$table->string('url')->nullable();
				$table->string('ip')->nullable();
				$table->string('method')->nullable();
				$table->text('header')->nullable();
				$table->text('request')->nullable();
				$table->text('old_value')->nullable();
				$table->text('new_value')->nullable();
				$table->longText('message')->nullable();
				$table->longText('trace')->nullable();
				$table->nullableMorphs('action_onable');
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
        Schema::dropIfExists('site_logs');
    }
}
