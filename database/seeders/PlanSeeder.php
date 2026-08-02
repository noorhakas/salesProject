<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\User;
use App\Enums\PlanStatusEnum;
use App\Enums\PositionKey;
use Carbon\Carbon;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $salesReps = User::whereHas('userposition', function ($q) {
            $q->where('ps_key', PositionKey::SALES_REP->value);
        })->get();

        if ($salesReps->isEmpty()) {
            $this->command->warn('No sales reps found. Run UserSeeder first.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | For each sales rep, create a mixed set of plans (pending, in progress,
        | completed, rejected, upcoming) so the dashboard/statistics have real
        | variety to show. The approver is always the rep's ACTUAL supervisor
        | (manager_id), never a hardcoded user - so it stays consistent even if
        | UserSeeder changes how many reps/supervisors exist.
        |--------------------------------------------------------------------------
        */

        foreach ($salesReps as $index => $rep) {
            $supervisor = $rep->manager; // belongsTo User via manager_id

            $type = $index % 2; // alternate weekly/monthly

            $plansData = [
                // Pending - waiting for supervisor approval
                [
                    'user_id'    => $rep->id,
                    'type'       => $type,
                    'start_date' => Carbon::now()->addDays(3),
                    'end_date'   => Carbon::now()->addDays(7),
                    'status'     => PlanStatusEnum::Pending,
                    'approved_or_rejected_by' => 0,
                ],

                // Accepted, currently running (In Progress)
                [
                    'user_id'    => $rep->id,
                    'type'       => $type,
                    'start_date' => Carbon::now()->subDays(2),
                    'end_date'   => Carbon::now()->addDays(2),
                    'status'     => PlanStatusEnum::Accepted,
                    'approved_or_rejected_by' => $supervisor?->id ?? 0,
                ],

                // Accepted, window already passed (Completed)
                [
                    'user_id'    => $rep->id,
                    'type'       => $type,
                    'start_date' => Carbon::now()->subDays(15),
                    'end_date'   => Carbon::now()->subDays(10),
                    'status'     => PlanStatusEnum::Accepted,
                    'approved_or_rejected_by' => $supervisor?->id ?? 0,
                ],

                // Rejected
                [
                    'user_id'    => $rep->id,
                    'type'       => 1 - $type,
                    'start_date' => Carbon::now()->addDays(1),
                    'end_date'   => Carbon::now()->addDays(5),
                    'status'     => PlanStatusEnum::Rejected,
                    'approved_or_rejected_by' => $supervisor?->id ?? 0,
                ],

                // Accepted, not started yet (Upcoming)
                [
                    'user_id'    => $rep->id,
                    'type'       => $type,
                    'start_date' => Carbon::now()->addDays(20),
                    'end_date'   => Carbon::now()->addDays(25),
                    'status'     => PlanStatusEnum::Accepted,
                    'approved_or_rejected_by' => $supervisor?->id ?? 0,
                ],
            ];

            foreach ($plansData as $data) {
                Plan::create($data);
            }
        }
    }
}