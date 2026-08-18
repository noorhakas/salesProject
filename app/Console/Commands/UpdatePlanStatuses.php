<?php

namespace App\Console\Commands;

use App\Enums\PlanStatusEnum;
use App\Models\Plan;
use Illuminate\Console\Command;

class UpdatePlanStatuses extends Command
{
    protected $signature = 'plans:update-statuses';

    protected $description = 'Update completed and expired plans';

    public function handle(): int
    {
        $today = now()->toDateString();

        // Accepted plans that have ended -> Completed
        Plan::query()
            ->where('status', PlanStatusEnum::Accepted)
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => PlanStatusEnum::Completed,
            ]);

        // Pending plans that have ended -> Expired
        Plan::query()
            ->where('status', PlanStatusEnum::Pending)
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => PlanStatusEnum::Expired,
            ]);

        $this->info('Plan statuses updated successfully.');

        return self::SUCCESS;
    }
}