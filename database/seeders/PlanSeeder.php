<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\User;
use App\Enums\PlanStatusEnum;
use Carbon\Carbon;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $manager    = User::where('email', 'manager@gmail.com')->first();
        $supervisor = User::where('email', 'supervisor@gmail.com')->first();
        $sales1     = User::where('email', 'sales1@gmail.com')->first();
        $sales2     = User::where('email', 'sales2@gmail.com')->first();

        if (!$sales1 || !$sales2) {
            $this->command->warn('Sales users not found. Run UserSeeder first.');
            return;
        }

        $plansData = [
            // خطة Pending لسه منتظرة موافقة السوبرفايزر
     /*       [
                'user_id'     => $sales1->id,
                'type'        => 0,
                'start_date'  => Carbon::now()->addDays(3),
                'end_date'    => Carbon::now()->addDays(7),
                'status'      => PlanStatusEnum::Pending,
                'approved_or_rejected_by' => 0,
            ],

            // خطة Accepted، شغالة دلوقتي (In Progress)
            [
                'user_id'     => $sales1->id,
                'type'        => 0,
                'start_date'  => Carbon::now()->subDays(2),
                'end_date'    => Carbon::now()->addDays(2),
                'status'      => PlanStatusEnum::Accepted,
                'approved_or_rejected_by' => $supervisor?->id,
            ],

            // خطة Accepted وخلصت (Completed)
            [
                'user_id'     => $sales1->id,
                'type'        => 0,
                'start_date'  => Carbon::now()->subDays(15),
                'end_date'    => Carbon::now()->subDays(10),
                'status'      => PlanStatusEnum::Accepted,
                'approved_or_rejected_by' => $supervisor?->id,
            ],

            // خطة Rejected
            [
                'user_id'     => $sales2->id,
                'type'        => 1,
                'start_date'  => Carbon::now()->addDays(1),
                'end_date'    => Carbon::now()->addDays(5),
                'status'      => PlanStatusEnum::Rejected,
                'approved_or_rejected_by' => $supervisor?->id,
            ],*/

            // خطة Pending لسيلز 2
            [
                'user_id'     => $sales2->id,
                'type'        => 0,
                'start_date'  => Carbon::now()->addDays(4),
                'end_date'    => Carbon::now()->addDays(10),
                'status'      => PlanStatusEnum::Pending,
                'approved_or_rejected_by' => 0,
            ],

            // خطة Upcoming (مقبولة بس لسه ماجتش)
            [
                'user_id'     => $sales2->id,
                'type'        => 0,
                'start_date'  => Carbon::now()->addDays(20),
                'end_date'    => Carbon::now()->addDays(25),
                'status'      => PlanStatusEnum::Accepted,
                'approved_or_rejected_by' => $manager?->id,
            ],
        ];

        foreach ($plansData as $data) {
            Plan::create($data);
        }
    }
}