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
use App\Enums\ScheduleStatusEnum;

class VisitController extends Controller
{
	
	public function CreateVisitSchedule(Request $request ,VisitScheduleRepository $scheduleRepository ){
		$theMonth = isset($request->date) && !empty($request->date) ?Carbon::parse($request->date)->format("m") : Carbon::now()->format("m") ;
        $theYear =  isset($request->date) && !empty($request->date) ?Carbon::parse($request->date)->format("Y") : Carbon::now()->format("Y");
		
		$days = Carbon::parse($request->date)->daysInMonth; 
        $firstDay  = Carbon::parse($request->date)->firstOfMonth()->toDateString(); 
        $user= isset($request->user_id) ? User::find($request->user_id) :  auth()->user();
		$scheduleResult = $scheduleRepository->createSchedule(["date"=> $request->date ,"month"=>$theMonth,"year"=>$theYear ,'days'=>$days ,'firstDay'=>$firstDay ,'user'=>$user]);

          		return  $this->response_api(true,trans('messages.success'),$scheduleResult);

	}

	public function submitSchedule(ScheduleRequest $request){
		\DB::beginTransaction();
        try {
				$status = (ScheduleStatusEnum::Pending)["id"];
				$user_id = $request->user_id ?? auth()->user()->id;
				$validate_data = array_merge(['status'=>$status],$request->validated());

				Visit::updateOrCreate(['customer_id'=>$request->customer_id,'user_id'=>$user_id,'visit_date'=>$request->visit_date],$validate_data);

			\DB::commit();
			return $this->response_api(true, trans('messages.success'));
		} catch (\Exception $e) {
					\DB::rollback();
					return $this->response_api(false, trans('messages.server_error'));
		}			
 	}


	 public function submitMultipleSchedule(ScheduleRequest $request){
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

	public function getDailyplannedvisits($date = null){
		   $today = isset($date) && !empty($date) ? Carbon::now()->today()->toDateString() : Carbon::parse($date)->toDateString();
           $status = (ScheduleStatusEnum::Confirmed)["id"];

				$visitScheduleRepository = new VisitScheduleRepository();
				$visits = $visitScheduleRepository->getUserVisitsByDataAndStatus(["date"=>$today , 'status'=>$status]);
		   return $this->response_api(true, trans('messages.success'),$visits);
	}



	public function submitUnplannedVisits(Request $request){

		
		$visitScheduleRepository = new VisitScheduleRepository();
		$visits = $visitScheduleRepository->submitPannedOrUnplannedVisit($request->all());
		// \DB::beginTransaction();
        // try {
		// 		$status = (ScheduleStatusEnum::Pending)["id"];
		// 		$user_id = $request->user_id ?? auth()->user()->id;
		// 		$validate_data = array_merge(['status'=>$status],$request->validated());

		// 		Visit::updateOrCreate(['customer_id'=>$request->customer_id,'user_id'=>$user_id,'visit_date'=>$request->visit_date],$validate_data);

		// 	\DB::commit();
		// 	return $this->response_api(true, trans('messages.success'));
		// } catch (\Exception $e) {
		// 			\DB::rollback();
		// 			return $this->response_api(false, trans('messages.server_error'));
		// }			
 	}
	
}
