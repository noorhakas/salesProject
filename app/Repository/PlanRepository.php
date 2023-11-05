<?php

namespace App\Repository;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Plan;
use App\Models\Visit;

class PlanRepository{
       public function submitNewPlan($request){
		try {
			\DB::beginTransaction();
			$visit_list = collect($request->visit_list);
			$min_date = $visit_list->min('visit_date');
			$max_date = $visit_list->max('visit_date');
            $user_id = auth()->user()->id??0;
			//create plan 
			$planCreated = $this->createPlan(["min_date"=>$min_date , "max_date"=>$max_date ,"type"=>$request->type]);
				foreach($visit_list as $single){
					$status = (VisitStatusEnum::Pending)["id"];
					$displayData = ['plan_id'=>$planCreated->id,'customer_id'=>$single['account_id'],'status'=>$status,'user_id'=>$user_id,'visit_date'=>$single['visit_date'],'start_time'=>$single['start_time'],'end_time'=>$single['end_time']];			
					Visit::updateOrCreate(['customer_id'=>$single['account_id'],'user_id'=>$user_id,'visit_date'=>$single['visit_date']],$displayData);
				}
			\DB::commit();
			 } catch (\Exception $e) {
			 			\DB::rollback();
			}	
	   } 

		protected function createPlan(array $data){
				return Plan::updateOrCreate(['user_id'=>auth()->user()->id??0 , 'start_date'=>$data['min_date'] ],[
					'user_id'=>auth()->user()->id??0,
					'start_date'=>$data['min_date'],
					"end_date"=>$data['max_date'],
					"type"=>$data['type']
				]);
		}	 
  
  
      public function getPlanDetails(Plan $plan){
		
		$first_date = $plan->start_date;
		$days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));
        $listOfDates =$this->displayListOFDates(["firstDate"=>$first_date , "days"=>$days]);
           return  $listOfDates;
	  }

	  protected function displayListOFDates(array $arr){
		$date_arr = [];
		for($i = 0;$i<$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDate'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>substr($dateObj->dayName,0,3)];
		}
		return $date_arr;
	}

}

