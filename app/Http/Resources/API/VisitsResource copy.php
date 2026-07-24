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
		$statusAsString = (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $this->status !=2  ? 'Missed' : VisitStatusEnum::toString($this->status);
       	        $status = (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $this->status != 2  ? 5 : $this->status;

   /* if($this->customer){
       $base = new CustomerResource($this->customer);
	}else{
		$new_data = collect(['image'=>asset('/assets/img/avatar_logo.jpg') ,'specialty_name'=>''
	                ,'work_days'=>[],'work_start_time'=>'','work_end_time'=>'','work_time'=>[],'work_days_AsString'=>'']);
		    $base = collect(new AccountResource($this->account))->merge($new_data);
	   }
       */
		return  [
            'id' => $this->id,
            'customer' => new CustomerResource($this->customer),
            'account_id'=>$this->account?$this->account->id:0,
          'account'=>$this->account?$this->account->name:'',
            'brick'=>$this->account?$this->account?->brick->name:'',
			'user_name'=>optional($this->user)->name,
            'combine_with'=>$this->combine_with??0,
            'combine_user_name'=>optional($this->doubleVisit)->name,
			'type'=>($this->type == 1)? 'unplanned' : 'planned',
			'plan_code'=>optional($this->plan)->Uuid,
			'status'=>$status,
			'statusAsString'=>$statusAsString,
			'visit_date'=>Carbon::parse($this->visit_date)->toDateString(),
			'short_visit_date'=>Carbon::parse($this->visit_date)->format("M-d"),
			'start_time'=>Carbon::parse($this->start_time)->format("H:i:s"),
			'end_time'=>Carbon::parse($this->end_time)->format("H:i:s"),
			 'actual_start_time'=> $this->actual_start_date ? Carbon::parse($this->actual_start_date)->format("Y-m-d H:i:s") : '',
			'actual_end_time'=>$this->actual_end_date ? Carbon::parse($this->actual_end_date)->format("Y-m-d H:i:s") : '',
            'actual_visit_date'=> $this->actual_start_date ? Carbon::parse($this->actual_start_date)->format("Y-m-d") : '',
			'actual_start_visit_time'=>$this->actual_start_date ? Carbon::parse($this->actual_start_date)->format("H:i:s") : '',
            'actual_end_visit_time'=>$this->actual_end_date ? Carbon::parse($this->actual_end_date)->format("H:i:s") : '',
            'notes'=>(string)$this->notes,
            'user_location_lat'=>(string)$this->user_location_lat,
            'user_location_lng'=>(string)$this->user_location_lng,
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
