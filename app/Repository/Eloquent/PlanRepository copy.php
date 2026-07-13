<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\PlanInterface;
use App\Http\Resources\API\PlansResource;
use App\Models\Plan;
use App\Models\PlanStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\Notification;
use Carbon\Carbon;
use App\Enums\VisitStatusEnum;

class PlanRepository implements PlanInterface
{
     public function getMyPlans($request){
        $limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

		$recent_plans = User::getCurrentPlan();
		$recent_plans_collection= $recent_plans ? new PlansResource($recent_plans) : (object)[];
		$previous_plans =  auth()->user()->plans()->filter($request)->when($recent_plans , fn($q,$v) => $q->where('id','!=',$v->id))->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data = ["recent_plans"=>$recent_plans_collection ,"previous_plans"=> PlansResource::collection($previous_plans)];
	 
			return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	}

	public function getALL($request){
		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;
		$plans =  Plan::select('plans.*')->join('users','users.id','=','plans.user_id')->filter($request)->orderBy('plans.created_at','DESC')->paginate($limit);
		    $data =  PlansResource::collection($plans);

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];	
	}

	public function createNewPlan($request){
		try {
		//	\DB::beginTransaction();
			$visit_list = collect($request->visit_list);
			$min_date = $visit_list->min('visit_date');
			$max_date = $visit_list->max('visit_date');
                        $user_id = auth()->user()->id??0;
			//create plan 
			$planCreated = $this->createPlan(["min_date"=>$min_date , "max_date"=>$max_date ,"type"=>0]);
				foreach($visit_list as $single){
					$status = (VisitStatusEnum::Pending)["id"];
					$start_time = Carbon::parse($single['start_time'])->format('H:i:s');
					$end_time = Carbon::parse($single['end_time'])->format('H:i:s');  
                    $doctor_id = isset($single['doctor_id']) && !empty($single['doctor_id']) ? $single['doctor_id'] : 0;
                    $combine_with = isset($single['combine_with']) && !empty($single['combine_with']) ? $single['combine_with'] : 0;
					$displayData = ['account_id'=>$single['account_id'],'plan_id'=>$planCreated->id,'customer_id'=>$doctor_id,'combine_with'=>$combine_with,'status'=>$status,'user_id'=>$user_id,'visit_date'=>$single['visit_date'],'start_time'=>$start_time,'end_time'=>$end_time];			
					Visit::updateOrCreate(['account_id'=>$single['account_id'],'customer_id'=>$doctor_id,'user_id'=>$user_id,'visit_date'=>$single['visit_date']],$displayData);
				}

			    (new Notification)->sendNotification(['tokens'=>getUserFcmTokens(),'notify_title'=>'new_plan',
										'notify_body'=>'created_new_plan',
										'title' => __('messages.new_plan'),
										'msg' => __('messages.created_new_plan', ['vName' => auth()->user()->name]),
										'notify_userId'=>0,'model_type'=>'plan',
										'tiDeviceType'=>1,'notify_type'=>1,
										'model_id'=>$planCreated->id]);	
			return ['status'=>true,'message'=> trans('messages.plan_reviewed')];
		//\DB::commit();
		 } catch (\Exception $e) {
		// \DB::rollback();
		  return ['status'=>false,'message'=> trans('messages.server_error')];
		}	
	   } 



	  public function show($plan_id){
		try {	
			$plan = Plan::find($plan_id);
			if(!$plan)
				return ['status'=>false,'message'=> trans('messages.data_not_found')];

			if($plan && $plan->status == 0 && auth()->user()->position == 3)
				return ['status'=>false,'message'=> trans('messages.plan_reviewed')];	
			
			if($plan && $plan->status == 2 && auth()->user()->position == 3){
				$plan_Note = optional($plan->plan_status()->where('status',2)->first())->note;
				return ['status'=>false,'message'=> trans('messages.plan_rejected') .'. '.$plan_Note];	
			}
				

			$first_date = $plan->start_date;
			$days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));
			$listOfDates =$this->displayListOFDates(["firstDate"=>$first_date , "days"=>$days]);	
				$data = ["plan"=>new PlansResource($plan),"listOfDates"=>$listOfDates];
			return ['status'=>true,'message'=> trans('messages.success'),"data"=>$data]; 
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
		return Plan::updateOrCreate(['user_id'=>auth()->user()->id??0 , 'start_date'=>$data['min_date'],'status'=>0 ],[
			'user_id'=>auth()->user()->id??0,
			'start_date'=>$data['min_date'],
			"end_date"=>$data['max_date'],
			"type"=>$data['type'],
		]);
    }

	

	public function deletePlan($plan)
    {
		try {	
			if(!$plan)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$plan->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}
    }

	public function AcceptOrRejectPlan($request)
{
    try {
        // Retrieve and validate request data
        $plan_id = $request->plan_id;
        $status = $request->status;  // 1 = Accepted, 2 = Rejected
        $note = $request->note ?? '';
        $approved_by = auth()->id() ?? 0;

        // Find the plan and associated user
        $plan = Plan::findOrFail($plan_id);
        $user = User::findOrFail($plan->user_id);

        // Update plan status and approval info
        $plan->update([
            'status' => $status,
            'approved_or_rejected_by' => $approved_by
        ]);

        // Update or create a record in PlanStatus
        PlanStatus::updateOrCreate(
            ['plan_id' => $plan_id, 'approved_or_rejected_by' => $approved_by],
            array_merge($request->validated(), [
                'status' => $status,
                'approved_or_rejected_by' => $approved_by
            ])
        );

        // Notification settings based on status
        $notificationSettings = [
            '1' => [
                'notify_title' => 'accept_plan',
                'notify_body' => 'manager_accept_plan',
                'title' => __('messages.accept_plan'),
                'msg' => __('messages.manager_accept_plan', ['vName' => auth()->user()->name])
            ],
            '2' => [
                'notify_title' => 'reject_plan',
                'notify_body' => 'manager_rejected_plan',
                'title' => __('messages.reject_plan'),
                'msg' => __('messages.manager_reject_plan', ['vName' => auth()->user()->name])
            ]
        ];

        $notificationData = $notificationSettings[$status];

        // Send notification to the plan owner
        $this->createNotification([
            'user_DeviceToken' => $user->DeviceToken,
            'notificationData' => $notificationData,
            'user_id' => $user->id,
            'model_type' => 'plan',
            'model_id' => $plan_id
        ]);

        // If plan is accepted, send visit request notifications
        if ($status == 1) {
            $allUsers = $plan->visits()
                ->join('users', 'users.id', '=', 'visits.combine_with')
                ->leftJoin('accounts', 'accounts.id', '=', 'visits.account_id')
                ->selectRaw('users.id, users.DeviceToken, visits.id as visit_id, accounts.name as account_name, accounts.id as account_id, visits.customer_id, visits.visit_date, visits.start_time, visits.end_time')
                ->where('combine_with', '>', 0)
                ->get();

            foreach ($allUsers as $single) {
                $notifyData = [
                    'notify_title' => 'visit_request',
                    'notify_body' => 'visit_notification_body',
                    'title' => __('messages.visit_request'),
                    'msg' => __('messages.visit_request_msg', [
                        'userName' => $user->name,
                        'doctorName' => $single->account_name . '-' . optional($single->customer)->name,
                        'dateTime' => $single->visit_date . ' at ' . $single->start_time
                    ])
                ];

                $this->createNotification([
                    'user_DeviceToken' => $single->DeviceToken,
                    'notificationData' => $notifyData,
                    'user_id' => $single->id,
                    'model_type' => 'visit_request',
                    'model_id' => $single->visit_id,
                    'account_id' => $single->account_id ?? 0,
                    'customer_id' => $single->customer_id ?? 0,
                    'visit_date' => $single->visit_date ?? '',
                    'visit_time' => $single->start_time ?? ''
                ]);
            }
        }

        return ["status" => true, "message" => trans('messages.success')];
    } catch (\Exception $e) {
        \Log::error("Plan approval failed: " . $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile());
        return ["status" => false, "message" => trans('messages.server_error')];
    }
}

    public function getSubordinatesPlans($request, array $subordinateIds)
    {
        $limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

        $plans = Plan::select('plans.*')
            ->join('users', 'users.id', '=', 'plans.user_id')
            ->whereIn('plans.user_id', $subordinateIds)
            ->filter($request)
            ->orderBy('plans.created_at', 'DESC')
            ->paginate($limit);

        return ["status" => true, "message" => trans('messages.success'), 'data' => PlansResource::collection($plans)];
    }

    public function showForManager($plan_id, array $subordinateIds)
    {
        try {
            $plan = Plan::whereIn('user_id', $subordinateIds)->find($plan_id);

            if (!$plan) {
                return ['status' => false, 'message' => trans('messages.data_not_found')];
            }

            $first_date = $plan->start_date;
            $days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));
            $listOfDates = $this->displayListOFDates(["firstDate" => $first_date, "days" => $days]);

            $data = ["plan" => new PlansResource($plan), "listOfDates" => $listOfDates];

            return ['status' => true, 'message' => trans('messages.success'), "data" => $data];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => trans('messages.server_error')];
        }
    }

protected function createNotification(array $data)
{
    (new Notification)->sendNotification([
        'tokens' => $data['user_DeviceToken'],
        'notify_title' => $data['notificationData']['notify_title'],
        'notify_body' => $data['notificationData']['notify_body'],
        'title' => $data['notificationData']['title'],
        'msg' => $data['notificationData']['msg'],
        'notify_userId' => $data['user_id'],
        'model_type' => $data['model_type'],
        'tiDeviceType' => 1,
        'notify_type' => 0,
        'model_id' => $data['model_id'],
        'account_id' => $data['account_id'] ?? 0,
        'customer_id' => $data['customer_id'] ?? 0,
        'visit_date' => $data['visit_date'] ?? '',
        'visit_time' => $data['visit_time'] ?? '',
        'notify_type'=>1
    ]);
}





}
