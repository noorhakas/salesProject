<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\PlanInterface;
use App\Http\Resources\API\PlansResource;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class PlanRepository implements PlanInterface
{
     public function getMyPlans($request){
        $limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

		$recent_plans = User::getCurrentPlan();
		$previous_plans =  auth()->user()->plans()->filter($request)->when($recent_plans , fn($q,$v) => $q->where('id','!=',$v->id))->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data = ["recent_plans"=>new PlansResource($recent_plans) ,"previous_plans"=> PlansResource::collection($previous_plans)];
	 
			return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	}

	public function getALL($request){
		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;
		$user = ($request->user_id) ? User::find($request->user_id) : auth()->user(); 
		$plans =  $user->plans()->filter($request)->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data =  PlansResource::collection($plans);

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];	
	}

	public function createNewPlan($request){
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

			$this->sendNotification(['model_id'=>$planCreated->id]);	
			return ['status'=>true,'message'=> trans('messages.success')];
		\DB::commit();
		 } catch (\Exception $e) {
		  \DB::rollback();
		  return ['status'=>false,'message'=> trans('messages.server_error')];
		}	
	   } 



	  public function show($plan_id){
		try {	
			$plan = Plan::find($plan_id);
			if(!$plan)
				return ['status'=>false,'message'=> trans('messages.data_not_found')];

			$first_date = $plan->start_date;
			$days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));
			$listOfDates =$this->displayListOFDates(["firstDate"=>$first_date , "days"=>$days]);	
				$data = ["plan"=>new PlansResource($plan),"listOfDates"=>$listOfDates];
			return ['status'=>true,'message'=> trans('messages.success'),$data]; 
			} catch (\Exception $e) {
			return ['status'=>false,'message'=> trans('messages.server_error')];
		  }
	  } 
     

	
	
	
	  protected function displayListOFDates(array $arr){
		$date_arr = [];
		for($i = 0;$i<=$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDate'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>substr($dateObj->dayName,0,3)];
		}
		return $date_arr;
	}

	protected function createPlan(array $data){
		return Plan::updateOrCreate(['user_id'=>auth()->user()->id??0 , 'start_date'=>$data['min_date'] ],[
			'user_id'=>auth()->user()->id??0,
			'start_date'=>$data['min_date'],
			"end_date"=>$data['max_date'],
			"type"=>$data['type']
		]);
    }

	protected function sendNotification(array $data){
		
		Notification::CreateNotify(['created_by'=>auth()->user()->id??0 , 
		       'model_id'=>$data['model_id'] , 'model_type'=>'plan',
		       'notify_userId'=>0,
			   'notify_type'=>1,
			   'notify_title'=>'new_plan',
			   'notify_body'=>'created_new_plan'
			]);
		
			$pushData = [
				'id' => $data['model_id'],
				'title' => __('message.new_plan'),
				'msg' => __('message.created_new_plan', ['vName' => auth()->user()->vName]),
				'sound' => 'default',
				'modelId' =>  $data['model_id'],
				'topic'=>'admins'
			];
			__send_push(1,'admin_topic',$pushData);
	}

}