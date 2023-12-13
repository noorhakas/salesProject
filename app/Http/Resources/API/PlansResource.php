<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\VisitStatusEnum;
use App\Enums\PlanStatusEnum;

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
        switch($this->status){
            case 1:
				if(Carbon::parse($this->start_date)->toDateString() <= Carbon::now()->toDateString() && Carbon::parse($this->end_date)->toDateString() >= Carbon::now()->toDateString())
				{
					 $status = 4;
		             $statusAsString = 'In Progress';

				}elseif(Carbon::parse($this->end_date)->toDateString() < Carbon::now()->toDateString()){
                      $status = 3;
		             $statusAsString = 'Completed';
				}else{
					 $status = $this->status;
					 $statusAsString =  PlanStatusEnum::toString($this->status);
				}
				break;
			default:
			   if(Carbon::parse($this->end_date)->toDateString() < Carbon::now()->toDateString()){
                      $status = 3;
		             $statusAsString = 'Completed';
				}else{

			         $status = $this->status;
					 $statusAsString =  PlanStatusEnum::toString($this->status);
				}
			break;	
		}
		
       return  [
            'id' => $this->id,
			'plan_code'=>'#'.$this->Uuid,
			'user_name'=>$this->user?->name,
			'type'=>($this->type == 1)? 'monthly' : 'weekly',
			'start_date'=>Carbon::parse($this->start_date)->toDateString(),
			'end_date'=>Carbon::parse($this->end_date)->toDateString(),
			'Is_recent'=>(Carbon::parse($this->end_date) >= Carbon::today()) ? true : false,
			'status'=>$status,
			'statusAsString'=> $statusAsString,
            'total_visit'=>$this->visits()->selectRaw('count(*) as visit_count')->where('status',(VisitStatusEnum::Visited)["id"])->first()?->visit_count,
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
