<?php

namespace App\Repository;

use App\Models\Visit;
use App\Models\User;
use App\Models\Customer;
use App\Models\AccType;
use App\Models\VisitDetails;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Enums\VisitStatusEnum;
use App\Http\Resources\API\VisitsResource;


class VisitScheduleRepository{
        
	public function createSchedule($plan){
		 
		$start_date = $plan->start_date;
		$end_date = $plan->start_date;
		$days = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));

		$user = $plan->user; $searchDate = Carbon::parse($plan->start_date)->toDateString() .'|'.Carbon::parse($plan->end_date)->toDateString(); 	
		$acc_types = $this->getAccountTypeData();
		$list_days = $this->displayListOFDates(["firstDate"=> $start_date , "days"=>$days]);
        
		$all_Data =collect();
		foreach($acc_types as $j=>$type){
			$ACC_Data['acc_type'] = $type->name;
			$allCustomer = $this->getCustomerListByUser($user,$type->id);

			$all_cutomer_list =collect();
			foreach($allCustomer as $i=>$item){
				$Obj['id']= $item->id;
				$Obj['name'] = $item->name;
				$Obj['class_name']=optional($item->class)->name;
			  
				$listOfVisits = $user->visits()->selectRaw('visit_date as date ,status , 1 as disabled ')->whereDate('visit_date','>=',$start_date)->whereDate('visit_date','<=',$end_date)->where('customer_id',$item->id)->get()->keyBy('date');
				$result= $this->mergeDataByDate($list_days['dateWithSataus'],$listOfVisits);
				$Obj['dates']= $result;
				
				$all_cutomer_list->add($Obj); 
			}
			$ACC_Data['customer_list'] = $all_cutomer_list;

			$all_Data->add($ACC_Data);
	  }
		return ['User'=>(Object)["id"=>$user->id ,"name"=>$user->name],'CurrentDate'=>$searchDate,"listOfSataus"=> VisitStatusEnum::toArray(),"listOfDates"=>$list_days["listOfDates"] ,"schedule"=>$all_Data ];
	}


	protected function getCustomerListByUser(User $user,$type_id){
		return  $user->customers()->where('customers.acc_type_id',$type_id)->get();
	}

	protected function getAccountTypeData(){
        return AccType::get();
	}

	protected function displayListOFDates(array $arr){

		$dates = collect(); $date_arr=[];
		for($i = 0;$i<=$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDate'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>substr($dateObj->dayName,0,3)];

			$disabled = ($dateObj < Carbon::now() || $dateObj->dayName == "Friday" ) ? 1 : 0;
			$status = ($dateObj->dayName == "Friday") ? (VisitStatusEnum::Holiday)["id"] : (VisitStatusEnum::NOACTION)["id"];
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


}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}