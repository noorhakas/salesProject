<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //
             $table->bigInteger('phone')->nullable()->after('email');
             $table->bigInteger('whatsapp')->nullable()->after('phone');
             $table->bigInteger('manager_id')->nullable()->after('position');
             $table->bigInteger('active_code')->nullable()->after('manager_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
