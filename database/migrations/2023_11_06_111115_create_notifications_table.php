<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
			$table->id();
			$table->char('Uuid', 36)->nullable()->unique('Uuid');

            $table->unsignedbigInteger('user_id')->nullable();
            $table->tinyInteger('tiNotificationType')->default(1)->comment('1:Admin');
            $table->string('vTitle', 255)->nullable();
            $table->text('txBody');
            $table->tinyInteger('tiIsRead')->default(0)->comment('0:No,1:Yes');
			$table->unsignedbigInteger('model_id')->nullable();
			$table->string('model_type')->nullable();
			$table->unsignedbigInteger('created_by')->nullable();
			
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
        Schema::dropIfExists('notifications');
    }
}
