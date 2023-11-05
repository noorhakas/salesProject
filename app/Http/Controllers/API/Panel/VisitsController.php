<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Resources\API\VisitsResource;
use App\Models\Plan;
use App\Models\Visit;
use App\Repository\VisitRepository;


class VisitsController extends Controller
{
	 public function index(Request $request){

		$limit = (is_numeric($request->per_page) && ($request->per_page) > 0) ? $request->per_page : 20;
		$currentPlan = Plan::getCurrentPlan();
		$request->plan_id = ($request->plan_id)??$currentPlan->id;
			$visits = auth()->user()->visits()->filter($request)->paginate($limit);
			$data  = VisitsResource::collection($visits);
		return $this->response_api(true,trans('messages.success'),$data);
	 }

	 public function show($id){
		$visit = Visit::find($id);
		if(!$visit)
		    return $this->response_api(false, trans('messages.user_not_found'));
     
		$data = (new VisitRepository())->getVisitDetail($visit);

	    return $this->response_api(true, trans('messages.success'),$data);
	 }


}
