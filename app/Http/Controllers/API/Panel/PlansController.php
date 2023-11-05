<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Resources\API\PlansResource;
use App\Models\Plan;
use App\Repository\PlanRepository;


class PlansController extends Controller
{
	 public function index(Request $request){
		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

		$recent_plans = Plan::getCurrentPlan();
		$previous_plans =  auth()->user()->plans()->filter($request)->when($recent_plans , fn($q,$v) => $q->where('id','!=',$v->id))->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data = ["recen_plans"=>new PlansResource($recent_plans) ,"previous_plans"=> PlansResource::collection($previous_plans)];
		return $this->response_api(true,trans('messages.success'),$data);
	 }


	 public function store(Request $request){

		\DB::beginTransaction();
        try {
			 (new PlanRepository())->submitNewPlan($request);
		  \DB::commit();
		  return $this->response_api(true, trans('messages.success'));
		} catch (\Exception $e) {
					\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	 }


	 public function planDetail(){
		$plan_id = request()->get('plan_id')??Plan::getCurrentPlan();
		$plan = Plan::find($plan_id);
		if(!$plan)
		    return $this->response_api(false, trans('messages.user_not_found'));
     
        $planDates = (new PlanRepository())->getPlanDetails($plan);
		return $this->response_api(true, trans('messages.success'),["listOfDates"=>$planDates]);
	 }


}
