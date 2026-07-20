<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_customers', function (Blueprint $table) {
            //
             if (!Schema::hasColumn('user_customers', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_customers', function (Blueprint $table) {
            //
        });
    }
};
