<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Repository\Interfaces\PlanInterface;
use App\Models\User;
use App\Models\Plan;
use App\Http\Requests\API\PlanChangeStatusRequest;
use App\Http\Requests\API\PlanRequest;


class PlansController extends Controller
{
	public $IPlan;
    public function __construct(PlanInterface $IPlan)
    {
        $this->IPlan = $IPlan;
    }

	 public function index(Request $request){
		
		$response = $this->IPlan->getMyPlans($request);
		return $this->SendResponse($response);
	 }

	 public function getAllPlans(Request $request){

		$user = ($request->user_id) ? User::find($request->user_id) : auth()->user(); 
		$response = $this->IPlan->getAll($request,$user);
		return $this->SendResponse($response);
	 }


	 public function store(PlanRequest $request){
        
		$response = $this->IPlan->createNewPlan($request);
		 return $this->SendResponse($response);
	 }

	 public function planDetail(){
		$plan_id = request()->get('plan_id')??User::getCurrentPlan()?->id;
		$response = $this->IPlan->show($plan_id);
		 return $this->SendResponse($response);
	 }


      public function destroy(Plan $plan)
    {
		$response = $this->IPlan->deletePlan($plan);
		return $this->SendResponse($response);
    }


	public function AcceptPlan(PlanChangeStatusRequest $request)
    {
		$response = $this->IPlan->AcceptOrRejectPlan($request);
		return $this->SendResponse($response);
    }
	

	public function RejectPlan(PlanChangeStatusRequest $request)
    {
		$response = $this->IPlan->AcceptOrRejectPlan($request);
		return $this->SendResponse($response);
    }


}
