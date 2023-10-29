<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Repository\VisitScheduleRepository;
use Carbon\Carbon;
use App\Models\Visit;
use App\Models\User;
use App\Http\Requests\API\ScheduleRequest;


class VisitController extends Controller
{
	
	public function CreateVisitSchedule(Request $request ,VisitScheduleRepository $scheduleRepository ){
		$request_date = isset($request->date) && !empty($request->date) ?Carbon::parse($request->date)->format("m") : Carbon::now()->format("m") ;
		$days = Carbon::now()->month($request_date)->daysInMonth; 
        $firstDay  = Carbon::now()->month($request_date)->firstOfMonth()->toDateString(); 
        $user= isset($request->user_id) ? User::find($request->user_id) :  auth()->user();
		$scheduleResult = $scheduleRepository->createSchedule(["month"=>$request_date ,'days'=>$days ,'firstDay'=>$firstDay]);

          		return  $this->response_api(true,trans('messages.success'),$scheduleResult);

	}

	public function submitSchedule(ScheduleRequest $request){
		\DB::beginTransaction();
        try {
				$schedules = $request->all_data;
				$user_id = $request->user_id ?? auth()->user()->id;
				$result=[];
				foreach($schedules as $single){
					$item = $single['customer_id'];
					$displayData = ['customer_id'=>$single['customer_id'],'status'=>$single['status'],'user_id'=>$user_id,'visit_date'=>$single['date']];			
					Visit::updateOrCreate(['customer_id'=>$single['customer_id'],'user_id'=>$user_id,'visit_date'=>$single['date']],$displayData);
					}
			\DB::commit();
			return $this->response_api(true, trans('messages.success'));
		} catch (\Exception $e) {
					\DB::rollback();
					return $this->response_api(false, trans('messages.server_error'));
		}			
 	}
	
}
