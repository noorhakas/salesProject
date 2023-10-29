<?php

namespace App\Repository;

use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;


class VisitScheduleRepository{
        
	public function createSchedule(array $data){
		 
		$allCustomer = $this->getCustomerListByUser();
		$list_days = $this->displayListOFDates($data);
		$month = $data['month'];
		
		 $all_visits =collect();
		 foreach($allCustomer as $i=>$item){
             $Obj['name'] = $item->name;
			 $Obj['brick_name'] = optional($item->brick)->name;
			 $Obj['acc_type'] = optional($item->accType)->name;
		
			  $listOfVisits = auth()->user()->visits()->whereRaw('MONTH(visit_date)='.$month.' and customer_id ='.$item->id.' ')->get(['visit_date as date','status'])->keyBy('date');
		      $result= $this->mergeDataByDate($list_days['dateWithSataus'],$listOfVisits);
              $Obj['dates']= $result;
			  
			  $all_visits->add($Obj); 
		}

		return $all_visits;
	}


	protected function getCustomerListByUser(){
		return  auth()->user()->customers()->get();
	}


	protected function displayListOFDates(array $arr){

		$dates = collect(); $date_arr=[];
		for($i = 0;$i<$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDay'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>$dateObj->dayName];
			$dates[$date] = ["status"=>0];
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