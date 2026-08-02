<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\HomeInterface;
use App\Models\Plan;
use App\Models\Visit;
use App\Models\User;
use App\Models\Product;
use App\Models\Account;
use App\Models\Customer;
use App\Models\SiteLog;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\LogsResource;
use App\Enums\PositionKey;
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

	  protected function statistics()
	{
		$currentDate = Carbon::today();

		return [
			'total_admin' => User::where('is_admin', 1)->where('status', 1)->count(),

			'total_managers' => User::where('is_admin', '!=', 1)
				->whereHas('userposition', fn ($q) =>
					$q->where('ps_key','!=', PositionKey::SALES_REP->value)
				)->where('status', 1)->count(),

			'total_salesrep' => User::where('is_admin', '!=', 1)
				->whereHas('userposition', fn ($q) =>
					$q->where('ps_key', PositionKey::SALES_REP->value)
				)->where('status', 1)->count(),

			'total_products' => Product::count(),

			'total_accounts' => Account::count(),

			'total_customers' => Customer::count(),

			'total_visits' => Visit::has('plan')->whereDate('actual_start_date', $currentDate)
				->where('status', 2)->count(),

			'total_plans' => Plan::has('user')->whereDate('start_date', '<=', $currentDate)
				->where('end_date', '>=', $currentDate)->where('status', 1)->count(),
		];
	}

	 

	  public function getAllLogs(){

		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$logs = SiteLog::orderBy('site_logs.created_at','DESC')->paginate($limit); 
		   $data = LogsResource::collection($logs);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


	  

}