<?php

namespace App\Repository;

use App\Models\Visit;
use App\Models\User;
use App\Models\AccType;
use App\Models\Classes;
use App\Models\Plan;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Enums\VisitStatusEnum;
use App\Enums\DayOffEnum;
use App\Http\Resources\API\PlansResource;

class VisitScheduleRepository{

    public function createSchedule($request){

        $plan = Plan::find($request->plan_id);
        $start_date = $plan->start_date;
        $end_date = $plan->start_date;
        $days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));

        $user = $plan->user; $searchDate = Carbon::parse($plan->start_date)->toDateString() .'|'.Carbon::parse($plan->end_date)->toDateString(); 	
        $acc_types = $this->getAccountTypeData();
        $list_days = $this->displayListOFDates(["firstDate"=> $start_date , "days"=>$days]);

        $all_Data =collect();
            $allCustomer = $this->getCustomerListByUser($user,$plan,$request);

            foreach($allCustomer as $i=>$item){
                $class_id = ($item->customer_class_id) ? $item->customer_class_id : $item->account_class_id;
                    $Obj['id']= $item->id.'_'.$item->customer_id;
                $Obj['name'] = $item->customer_name;
                  $Obj['account_name'] = $item->name;
                $Obj['class_name']=$class_id && Classes::find($class_id)? Classes::find($class_id)->name:'';

                $listOfVisits = $user->visits()->selectRaw('visit_date as date , CASE WHEN visit_date < Date(NOW()) and visits.status = 0 THEN 5 ELSE visits.status END AS status, 1 as disabled ,combine_with ')->where(['visits.account_id'=>$item->id,'visits.customer_id'=>$item->customer_id,'visits.plan_id'=>$plan->id])->get()->keyBy('date');

                $result= $this->mergeDataByDate($list_days['dateWithSataus'],$listOfVisits);
                $Obj['dates']= $result;

                $all_Data->add($Obj); 
            }

         $planStatistics = $this->DrawVisitStatistics($request->plan_id);
        return ['User'=>(Object)["id"=>$user->id ,"name"=>$user->name],
             'CurrentDate'=>$searchDate,
             "listOfSataus"=> VisitStatusEnum::toArray(),
             "listOfDates"=>$list_days["listOfDates"] ,
             "schedule"=>$all_Data,"acc_types"=>$acc_types ,
             'plan'=>new PlansResource($plan),
             "plan_statistics"=>$planStatistics ];
    }


    protected function getCustomerListByUser(User $user,Plan $plan,$request){

        return $plan->visits()->join('accounts','accounts.id','=','visits.account_id')->
        selectraw('accounts.id,accounts.name,customers.name as customer_name,customers.id as customer_id,accounts.class_id as account_class_id,customers.class_id as customer_class_id')
        ->leftjoin('customers','customers.id','=','visits.customer_id')
        ->when($request->acc_type_id,fn($q, $v) =>$q->where('accounts.acc_type_id',$v))
        ->when(isset($request->status),function($q) use ($request){
            if($request->status == '-1')
              $q->where('visits.status', 0);	

            else
            $q->where('visits.status', $request->status);	
        })->distinct()->get();
    }

    protected function getAccountTypeData(){
        return AccType::get();
    }

    /**
     * Get weekly off days configured in Settings.
     * Returns array of DayOffEnum values (1-7), e.g. [6] for Friday.
     */
    protected function getWeeklyOffDays(): array
    {
        $setting = Setting::first();

        return $setting?->weekly_off_days ?? [];
    }

    protected function displayListOFDates(array $arr){

        $dates = collect(); $date_arr=[];

        $weeklyOffDays = $this->getWeeklyOffDays();

        for($i = 0;$i<=$arr['days'] ;$i++){
            $dateObj = Carbon::parse($arr['firstDate'])->addDays($i);
            $date = $dateObj->toDateString();
            $date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>substr($dateObj->dayName,0,3)];

            $disabled = 1;

            $dayOffValue = DayOffEnum::fromCarbon($dateObj->dayOfWeek)->value;

            $isDayOff = in_array($dayOffValue, $weeklyOffDays, true);

            $status = $isDayOff ? (VisitStatusEnum::Holiday)["id"] : (VisitStatusEnum::All)["id"];

            $dates[$date] = ["status"=>$status,"disabled"=>$disabled];
        }
        return ["listOfDates"=>$date_arr ,'dateWithSataus'=>$dates ];
    }


    public function mergeDataByDate(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $date => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                };
                $data[$date] = ReportData::make(array_merge(isset($data[$date]) ? $data[$date]->toArray() : ['date' => $date], $item->toArray()));
            }
        }
        return collect($data)->sortBy('date', SORT_REGULAR, false)->values();
    }


    public function DrawVisitStatistics($plan_id){

        return  Visit::selectRaw('visits.visit_date,
                sum(if(visits.status = 2, 1,0)) as visit_count,
                sum(if(visits.type = 0 , 1,0)) as pln_visit_count,
                sum(if(visits.type = 1 , 1,0)) as unpln_visit_count, 
                sum(if(visits.status = 0  and DATE(visits.visit_date) < CURDATE() , 1,0)) as missed_visit_count,
                sum(if(visits.status = 0  and DATE(visits.visit_date) > CURDATE(), 1,0)) as pending_count,users.name , users.id')->join('users','users.id','=','visits.user_id')
                ->join('plans','plans.id','=','visits.plan_id')->where('plans.id',$plan_id)->groupBy('visits.visit_date')->get();
      }


}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}