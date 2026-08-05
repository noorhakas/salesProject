<?php

namespace App\Http\Resources\API;

use App\Enums\PlanStatusEnum;
use App\Http\Resources\GlobalCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanDetailResource extends JsonResource
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

        return [
            'id'             => $this->id,
            'plan_code'      => '#' . $this->Uuid,
            'user' => new SalesRepProfileResource($this->user),
            'type'           => $this->type == 1 ? 'monthly' : 'weekly',
            'start_date'     => Carbon::parse($this->start_date)->toDateString(),
            'end_date'       => Carbon::parse($this->end_date)->toDateString(),
            'total_days'     => $this->total_days,
            'Is_recent'      => Carbon::parse($this->end_date) >= Carbon::today(),
           'status'         => $this->display_status,
           'statusAsString' => $this->display_status_as_string,
            'total_visit'    => $this->total_visits,
            'total_visits'         => $this->total_visits,
            'total_visited_visits' => $this->total_visited,
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