<?php

namespace App\Console\Commands;

use App\Models\Visit;
use App\Enums\VisitStatusEnum;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateVisitStatuses extends Command
{
    protected $signature = 'visits:update-statuses';

    protected $description = 'Mark overdue pending visits as missed';

    public function handle(): int
    {
        $today = Carbon::today();

        $updated = Visit::query()
            ->where('visit_date', '<', $today)
            ->where('status', VisitStatusEnum::Pending['id'])
            ->update([
                'status' => VisitStatusEnum::Missed['id'],
                'updated_at' => now(),
            ]);

        $this->info("Updated {$updated} visits to Missed.");

        return self::SUCCESS;
    }
}