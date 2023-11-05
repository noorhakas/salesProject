<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;

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
       return  [
            'id' => $this->id,
			'plan_code'=>'#'.$this->Uuid,
			'type'=>($this->type == 1)? 'monthly' : 'weekly',
			'start_date'=>Carbon::parse($this->start_date)->toDateString(),
			'end_date'=>Carbon::parse($this->end_date)->format("M-d"),
			'Is_recent'=>(Carbon::parse($this->end_date) >= Carbon::today()) ? true : false,
            'total_visit'=>0
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
