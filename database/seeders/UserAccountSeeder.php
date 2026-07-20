<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use App\Models\Customer;
use App\Models\UserAccounts;

class UserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesPosition = Position::where('ps_key', 'sales_rep')->first();

        if (!$salesPosition) {
            $this->command->warn('لازم تشغل UserSeeder الأول عشان يعمل position "sales_rep".');
            return;
        }

        $salesUsers = User::where('position', $salesPosition->id)->get();

        if ($salesUsers->isEmpty()) {
            $this->command->warn('مفيش يوزرز بوزيشن sales_rep. شغل UserSeeder الأول.');
            return;
        }

        $customers = Customer::whereNotNull('account_id')->get();

        if ($customers->isEmpty()) {
            $this->command->warn('مفيش Customers. شغل CustomerSeeder الأول قبل UserAccountSeeder.');
            return;
        }

        foreach ($salesUsers as $salesUser) {
            $count = min($customers->count(), mt_rand(2, 5));

            $assignedCustomers = $customers->random($count);

            if (!$assignedCustomers instanceof \Illuminate\Support\Collection) {
                $assignedCustomers = collect([$assignedCustomers]);
            }

            foreach ($assignedCustomers as $customer) {
                UserAccounts::firstOrCreate([
                    'user_id'     => $salesUser->id,
                    'customer_id' => $customer->id,
                    'account_id'  => $customer->account_id,
                ]);
            }
        }

        $this->command->info('تم ربط الـ Sales Reps بالـ Customers/Accounts بنجاح.');
    }
}