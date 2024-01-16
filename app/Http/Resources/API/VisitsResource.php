<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\VisitStatusEnum;

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
		$statusAsString = (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $this->status == 0  ? 'Missed' : VisitStatusEnum::toString($this->status);
       	$status = (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $this->status == 0  ? 5 : $this->status;

    if($this->customer){
       $base = new CustomerResource($this->customer);
	}else{
		$new_data = collect(['image'=>asset('/assets/img/avatar_logo.jpg') ,'specialty_name'=>''
	                ,'work_days'=>[],'work_start_time'=>'','work_end_time'=>'','work_time'=>[],'work_days_AsString'=>'']);
		    $base = collect(new AccountResource($this->account))->merge($new_data);
	   }
       
		return  [
            'id' => $this->id,
            'customer' => $base,//new CustomerResource($this->customer),
			'user_name'=>optional($this->user)->name,
			'type'=>($this->type == 1)? 'unplanned' : 'planned',
			'plan_code'=>optional($this->plan)->Uuid,
			'status'=>$status,
			'statusAsString'=>$statusAsString,
			'visit_date'=>Carbon::parse($this->visit_date)->toDateString(),
			'short_visit_date'=>Carbon::parse($this->visit_date)->format("M-d"),
			'start_time'=>Carbon::parse($this->start_time)->format("H:i a"),
			'end_time'=>Carbon::parse($this->end_time)->format("H:i a"),
			'actual_start_time'=> $this->actual_start_time ? Carbon::parse($this->actual_start_time)->format("Y-m-d H:i a") : '',
			'actual_end_time'=>$this->actual_end_time ? Carbon::parse($this->actual_end_time)->format("Y-m-d H:i a") : '',
			'note'=>$this->note,
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
