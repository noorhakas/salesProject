<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Repository\Interfaces\VisitInterface;
use App\Repository\VisitScheduleRepository;
use App\Models\Plan;



class VisitsController extends Controller
{
	public $IVisit;
    public function __construct(VisitInterface $IVisit)
    {
        $this->IVisit = $IVisit;
    }

	 public function index(Request $request){

		$response = $this->IVisit->getvisitsByPlan($request);
		return $this->SendResponse($response);
	 }

	 public function show($id){

		$response = $this->IVisit->getvisitDtail($id);
		return $this->SendResponse($response);
	 }

	 public function VisitAsSchedule(Request $request){
		$plan = Plan::find($request->plan_id);
		$scheduleResult =(new VisitScheduleRepository())->createSchedule($plan);

		return $this->SendResponse(["status"=>true, "message"=>trans('messages.success'),'data'=>$scheduleResult]);
	 }

	 public function store(Request $request){
        
		$response = $this->IVisit->submitVisit($request);
		 return $this->SendResponse($response);
	 }

	 public function visitCharts(Request $request){
           $response = $this->IVisit->getVisitCharts($request);
		     return $this->SendResponse($response);
	  }

	  public function AllVisits(){
		     $response = $this->IVisit->getAllVisits();
		     return $this->SendResponse($response);
	  }

	  public function UserVisits(Request $request){
			 $response = $this->IVisit->getVisitsByUserId($request);
		     return $this->SendResponse($response);
	  }



}
