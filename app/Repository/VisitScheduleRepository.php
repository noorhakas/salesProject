<?php

namespace App\Repository;

use App\Models\Visit;
use App\Models\User;
use App\Models\AccType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Enums\ScheduleStatusEnum;

class VisitScheduleRepository{
        
	public function createSchedule(array $data){
		 
		$acc_types = $this->getAccountTypeData();
		$list_days = $this->displayListOFDates($data);

		$all_Data =collect();
		foreach($acc_types as $j=>$type){
			$ACC_Data['acc_type'] = $type->name;
			$allCustomer = $this->getCustomerListByUser();
			$month = $data['month'];
		
			$all_cutomer_list =collect();
			foreach($allCustomer as $i=>$item){
				$Obj['id']= $item->id;
				$Obj['name'] = $item->name;
				$Obj['brick_name'] = optional($item->brick)->name;
				$Obj['acc_type'] = $type->name;
			
				$listOfVisits = auth()->user()->visits()->selectRaw('visit_date as date ,status , 1 as disabled ')->whereRaw('MONTH(visit_date)='.$month.' and customer_id ='.$item->id.' ')->get()->keyBy('date');
				$result= $this->mergeDataByDate($list_days['dateWithSataus'],$listOfVisits);
				$Obj['dates']= $result;
				
				$all_cutomer_list->add($Obj); 
			}
			$ACC_Data['customer_list'] = $all_cutomer_list;

			$all_Data->add($ACC_Data);
	  }
		return ["listOfSataus"=> ScheduleStatusEnum::toArray(),"listOfDates"=>$list_days["listOfDates"] ,"schedule"=>$all_Data];
	}


	protected function getCustomerListByUser(){
		return  auth()->user()->customers()->get();
	}

	protected function getAccountTypeData(){
        return AccType::get();
	}

	protected function displayListOFDates(array $arr){

		$dates = collect(); $date_arr=[];
		for($i = 0;$i<$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDay'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>$dateObj->dayName];

			$disabled = ($dateObj < Carbon::now()) ? 1 : 0;
			$status = ($dateObj->dayName == "Friday") ? (ScheduleStatusEnum::Holiday)["id"] : (ScheduleStatusEnum::NOACTION)["id"];
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