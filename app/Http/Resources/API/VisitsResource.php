<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\ScheduleStatusEnum;

class VisitsResource extends JsonResource
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
        $customer = (new CustomerResource($this->customer));
		
       return  [
            'id' => $this->id,
            'customer_name' =>  $customer?->name,
			'image' =>  $customer?->image,
			'specialty' =>  $customer?->specialty_name,
			'address' =>  $customer?->address,
			'type'=>($this->type == 1)? 'unplanned' : 'planned',
			'status'=>ScheduleStatusEnum::toString($this->status),
			'visit_date'=>Carbon::parse($this->visit_date)->toDateString(),
			'short_visit_date'=>Carbon::parse($this->visit_date)->format("M-d"),
			'start_time'=>Carbon::parse($this->start_time)->format("H:i a"),
			'end_time'=>Carbon::parse($this->end_time)->format("H:i a"),

        ];
    }
}
