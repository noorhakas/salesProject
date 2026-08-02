<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            if (Schema::hasColumn('attendances', 'shift_id')) {
                $table->dropColumn('shift_id');
            }

            if (Schema::hasColumn('attendances', 'worked_minutes')) {
                $table->dropColumn('worked_minutes');
            }

            if (Schema::hasColumn('attendances', 'late_minutes')) {
                $table->dropColumn('late_minutes');
            }

            if (Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->dropColumn('overtime_minutes');
            }

        });


        Schema::dropIfExists('overtime_requests');
    }


    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->unsignedBigInteger('shift_id')->nullable();
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);

        });
    }
};