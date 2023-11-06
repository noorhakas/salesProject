<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Resources\API\PlansResource;
use App\Models\Plan;
use App\Models\User;
use App\Repository\PlanRepository;


class PlansController extends Controller
{
	 public function index(Request $request){
		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

		$recent_plans = User::getCurrentPlan();
		$previous_plans =  auth()->user()->plans()->filter($request)->when($recent_plans , fn($q,$v) => $q->where('id','!=',$v->id))->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data = ["recent_plans"=>new PlansResource($recent_plans) ,"previous_plans"=> PlansResource::collection($previous_plans)];
		return $this->response_api(true,trans('messages.success'),$data);
	 }


	 public function getAllPlans(Request $request){
		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;
		$user = ($request->user_id) ? User::find($request->user_id) : auth()->user(); 
		$plans =  $user->plans()->filter($request)->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data =  PlansResource::collection($plans);
		return $this->response_api(true,trans('messages.success'),$data);
	 }


	 public function store(Request $request){
        
		$response = (new PlanRepository())->submitNewPlan($request);
			 return $this->SendResponse($response);
	 }


	 public function planDetail(){
		$plan_id = request()->get('plan_id')??User::getCurrentPlan()?->id;
		$plan = Plan::find($plan_id);
		if(!$plan)
		    return $this->response_api(false, trans('messages.user_not_found'));

        $listOfDates = (new PlanRepository())->getPlanDetails($plan);
		$planData = new PlansResource($plan);	
		return $this->response_api(true, trans('messages.success'),["plan"=>$planData,"listOfDates"=>$listOfDates]);
	 }


}
