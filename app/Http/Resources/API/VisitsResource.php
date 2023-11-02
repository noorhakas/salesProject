<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
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
			'class_name' =>  $customer?->class_name,
			'specialty' =>  $customer?->specialty_name,
			'address' =>  $customer?->address,
			'lat' =>  $customer?->lat,
			'long' =>  $customer?->lng,
			'type'=>($this->type == 1)? 'unplanned' : 'planned',
			'status'=>ScheduleStatusEnum::toString($this->status),
			'visit_date'=>Carbon::parse($this->visit_date)->toDateString(),
			'short_visit_date'=>Carbon::parse($this->visit_date)->format("M-d"),
			'start_time'=>Carbon::parse($this->start_time)->format("H:i a"),
			'end_time'=>Carbon::parse($this->end_time)->format("H:i a"),

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
