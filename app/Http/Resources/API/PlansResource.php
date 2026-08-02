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
            'status'         => $this->display_status,
            'statusAsString' => $this->display_status_as_string,
            'total_visit'    => $this->total_visits,
        ];
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