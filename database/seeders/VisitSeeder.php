<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Visit;
use App\Models\Account;
use App\Models\Customer;
use App\Enums\VisitStatusEnum;
use Carbon\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::find(1);//firstOrCreate(['name' => 'Default Account']);
        $customer = Customer::find(1);//::firstOrCreate(['name' => 'Default Customer']);

        $plans = Plan::all();

        if ($plans->isEmpty()) {
            $this->command->warn('No plans found. Run PlanSeeder first.');
            return;
        }

        foreach ($plans as $plan) {
            $days = $plan->start_date->diffInDays($plan->end_date) + 1;
            $days = min($days, 5); 

            for ($i = 0; $i < $days; $i++) {
                $visitDate = Carbon::parse($plan->start_date)->addDays($i);

                $status = match (true) {
                    $i === 0 => VisitStatusEnum::Visited['id'],
                    $i === 1 => VisitStatusEnum::Missed['id'],
                    default  => VisitStatusEnum::Pending['id'],
                };

                Visit::create([
                    'plan_id'     => $plan->id,
                    'user_id'     => $plan->user_id,
                    'account_id'  => $account->id,
                    'customer_id' => $customer->id,
                    'type'        => $plan->type,
                    'status'      => $status,
                    'visit_date'  => $visitDate,
                    'start_time'  => '09:00:00',
                    'end_time'    => '10:00:00',
                //    'confirmed_by' => $status == VisitStatusEnum::Visited['id'] ? $plan->user_id : null,
                    'confirmed_by' =>  $plan->user_id ,
                    'notes'       => null,
                    'user_location_lat' => 30.0444,
                    'user_location_lng' => 31.2357,
                    'actual_start_date' => $status === VisitStatusEnum::Visited['id'] ? $visitDate : null,
                    'actual_end_date'   => $status === VisitStatusEnum::Visited['id'] ? $visitDate : null,
                    'combine_with' => null,
                ]);
            }
        }
    }
}