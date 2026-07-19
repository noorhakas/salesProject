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
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'specialty_id')) {
                // لو فيه Foreign Key على العمود ده، لازم تتشال الأول
                // عدّل اسم القيد لو مختلف عندك (شوف ملحوظة تحت)
                // $table->dropForeign(['specialty_id']);
 
                $table->dropColumn('specialty_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
