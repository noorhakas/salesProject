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
        Schema::table('notifications', function (Blueprint $table) {
            // نشيل الأعمدة دي بس لو فعلاً موجودة في الجدول
            // (عشان الـ migration ميفشلش لو أصلاً مش موجودة)
            if (Schema::hasColumn('notifications', 'account_id')) {
                $table->dropColumn('account_id');
            }
            if (Schema::hasColumn('notifications', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('notifications', 'visit_date')) {
                $table->dropColumn('visit_date');
            }
            if (Schema::hasColumn('notifications', 'visit_time')) {
                $table->dropColumn('visit_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // بترجع الأعمدة تاني لو عملت rollback
            if (!Schema::hasColumn('notifications', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('model_type');
            }
            if (!Schema::hasColumn('notifications', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('notifications', 'visit_date')) {
                $table->date('visit_date')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('notifications', 'visit_time')) {
                $table->time('visit_time')->nullable()->after('visit_date');
            }
        });
    }
};