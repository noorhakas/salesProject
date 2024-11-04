<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\HomeInterface;
use App\Models\Plan;
use App\Models\Visit;
use App\Models\User;
use App\Models\Product;
use App\Models\SiteLog;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\LogsResource;
use App\Enums\UserPositionEnum;
use Carbon\Carbon;

class HomeRepository implements HomeInterface
{
      
	  public function getAll()
	  {
		$limit = 10;
		$currentDate = Carbon::today();
		$plans = Plan::select('plans.*')->whereHas('user', function ($query) {$query->where('users.status',1);})
		    ->where('plans.status',1)->whereDate('plans.start_date', '<=', $currentDate)->whereDate('plans.end_date', '>=', $currentDate)->orderBy('plans.created_at','DESC')->paginate($limit);
	  
		$visits = Visit::select('visits.*')->join('plans','plans.id','=','visits.plan_id')->whereHas('user', function ($query) {$query->where('users.status',1);})
		   ->whereDate('visits.actual_start_date', '=', $currentDate)->where('visits.status',2)->orderBy('visits.created_at','DESC')->paginate($limit);

		$logs = SiteLog::orderBy('site_logs.created_at','DESC')->paginate($limit);   

		$statistics = $this->statistics();

	 $data = ["current_plans"=>PlansResource::collection($plans)
				,"current_visits"=> VisitsResource::collection($visits)
				,"logs"=>LogsResource::collection($logs),
				"statistics"=>$statistics,
			 ];
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


	  protected function statistics(){
		   $currentDate = Carbon::today();

			return [
				 "total_users"=> User::selectRaw('count(*) as user_count')->where('position','!=',UserPositionEnum::MedicalRep)->where('status',1)->first()?->user_count,
                                  "total_medicalrep"=> User::selectRaw('count(*) as medicalrep_count')->where('position',UserPositionEnum::MedicalRep)->where('status',1)->first()?->medicalrep_count,
				 "total_products"=> Product::selectRaw('count(*) as product_count')->first()?->product_count,
				 "total_current_plans"=> Plan::has('user')->selectRaw('count(*) as plan_count')->whereDate('plans.start_date', '<=', $currentDate)->where('plans.end_date','>=',$currentDate)->where('plans.status',1)->first()?->plan_count,
			          "total_current_visits"=> Visit::has('plan')->selectRaw('count(*) as visit_count')->whereDate('visits.actual_start_date',$currentDate)->where('visits.status',2)->first()?->visit_count,
				];
	  }

	  public function getAllLogs(){

		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$logs = SiteLog::orderBy('site_logs.created_at','DESC')->paginate($limit); 
		   $data = LogsResource::collection($logs);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


	  

}
