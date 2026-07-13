<?php

namespace App\Http\Resources\API;

use App\Enums\PlanStatusEnum;
use App\Http\Resources\GlobalCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlansResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        [$status, $statusAsString] = $this->resolveDisplayStatus();

        return [
            'id'             => $this->id,
            'plan_code'      => '#' . $this->Uuid,
            'user_name'      => $this->user?->name,
            'type'           => $this->type == 1 ? 'monthly' : 'weekly',
            'start_date'     => Carbon::parse($this->start_date)->toDateString(),
            'end_date'       => Carbon::parse($this->end_date)->toDateString(),
            'total_days'     => $this->total_days,
            'Is_recent'      => Carbon::parse($this->end_date) >= Carbon::today(),
            'status'         => $status,
            'statusAsString' => $statusAsString,
            'total_visit'    => $this->total_visits,
        ];
    }

    /**
     * The stored `status` (Pending / Accepted / Rejected) isn't always
    */
    protected function resolveDisplayStatus(): array
    {
        $today = Carbon::now()->toDateString();
        $startDate = Carbon::parse($this->start_date)->toDateString();
        $endDate = Carbon::parse($this->end_date)->toDateString();

        if ((int) $this->status === PlanStatusEnum::Accepted) {
            if ($startDate <= $today && $endDate >= $today) {
                return [PlanStatusEnum::InProgress, PlanStatusEnum::toString(PlanStatusEnum::InProgress)];
            }

            if ($endDate < $today) {
                return [PlanStatusEnum::Completed, PlanStatusEnum::toString(PlanStatusEnum::Completed)];
            }

            if ($startDate > $today) {
                return [PlanStatusEnum::Upcoming, PlanStatusEnum::toString(PlanStatusEnum::Upcoming)];
            }
        }

        // Pending or Rejected: still shown as "Completed" once the plan's
        if ($endDate < $today && (int) $this->status !== PlanStatusEnum::Rejected) {
            return [PlanStatusEnum::Completed, PlanStatusEnum::toString(PlanStatusEnum::Completed)];
        }

        return [$this->status, PlanStatusEnum::toString($this->status)];
    }

    public static function collection($resource)
    {
        return tap(new GlobalCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }
}