<?php

namespace App\Repository;

use App\Models\Visit;
use App\Models\User;
use App\Models\AccType;
use App\Models\VisitDetails;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Enums\ScheduleStatusEnum;
use App\Http\Resources\API\VisitsResource;


class VisitScheduleRepository{
        
	public function createSchedule(array $data){
		 
		$user = $data['user']; $searchDate = $data['date']; $month = $data['month']; $year = $data['year'];	
		$acc_types = $this->getAccountTypeData();
		$list_days = $this->displayListOFDates($data);
        
		$all_Data =collect();
		foreach($acc_types as $j=>$type){
			$ACC_Data['acc_type'] = $type->name;
			$allCustomer = $this->getCustomerListByUser($user,$type->id);

			$all_cutomer_list =collect();
			foreach($allCustomer as $i=>$item){
				$Obj['id']= $item->id;
				$Obj['name'] = $item->name;
				$Obj['class_name']=optional($item->class)->name;
			  
				$listOfVisits = $user->visits()->selectRaw('visit_date as date ,status , 1 as disabled ')->whereRaw('MONTH(visit_date)='.$month.' and YEAR(visit_date)='.$year.' and customer_id ='.$item->id.' ')->get()->keyBy('date');
				$result= $this->mergeDataByDate($list_days['dateWithSataus'],$listOfVisits);
				$Obj['dates']= $result;
				
				$all_cutomer_list->add($Obj); 
			}
			$ACC_Data['customer_list'] = $all_cutomer_list;

			$all_Data->add($ACC_Data);
	  }
		return ['User'=>(Object)["id"=>$user->id ,"name"=>$user->name],'CurrentDate'=>$searchDate,"listOfSataus"=> ScheduleStatusEnum::toArray(),"listOfDates"=>$list_days["listOfDates"] ,"schedule"=>$all_Data ];
	}


	protected function getCustomerListByUser(User $user,$type_id){
		return  $user->customers()->where('customers.acc_type_id',$type_id)->get();
	}

	protected function getAccountTypeData(){
        return AccType::get();
	}

	protected function displayListOFDates(array $arr){

		$dates = collect(); $date_arr=[];
		for($i = 0;$i<$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDay'])->addDays($i);
			$date = $dateObj->toDateString();
			$date_arr[] = ["date"=>$date ,"number"=>$dateObj->day ,"day"=>substr($dateObj->dayName,0,3)];

			$disabled = ($dateObj < Carbon::now() || $dateObj->dayName == "Friday" ) ? 1 : 0;
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


	public function getUserVisitsByDate(array $data){

        $uservisitQuery = $data['user']->visits()->whereDate('visits.visit_date',$data['date'])
		    // ->when($data['status'] ,fn($q, $v) =>$q->where('visits.status',$v))
			 ->when($data['search'] ,fn($q, $v) =>$q->where('customers.name', 'like', "%{$v}%"));
		
		 $uservisits = (clone $uservisitQuery)->paginate($data['limit']);
		  return VisitsResource::collection($uservisits);
	}

	public function getAllVisits($request){

		$limit = $request->per_page ?? 20;
		$date = isset($request->date) && !empty($request->date) ? Carbon::now()->today()->toDateString() : Carbon::parse($request->date)->toDateString();

           $all_Visits = Visit::join('users', 'visits.user_id', '=', 'users.id')
							->join('customers', 'visits.customer_id', '=', 'customers.id')
						//	->when($request->get('user_id') ,fn($q, $v) =>$q->where('users.id', $v))
						//	->when($request->get('customer_id') ,fn($q, $v) =>$q->where('customers.id', $v))
						//	->when(isset($date) && !empty($date),fn($q, $v) =>$q->where('visits.visit_date', $date))
							->select('visits.*')->paginate($limit);
		   return VisitsResource::collection($all_Visits);
		 // return $all_Visits;
	}


	public function submitPannedOrUnplannedVisit(array $data){
     \DB::beginTransaction();
        try {
			$createdVisit = Visit::updateOrCreate(['customer_id'=>$data['customer_id'],'user_id'=>$data['user_id'],'visit_date'=>$data['visit_date']],$data);
			$items = [];
			foreach($data['items'] as $i=>$single)
			{
				$items[] = ['visit_id'=>$createdVisit->id,'item_id'=>$single['item_id'] ,'count_of_sample'=>$single['sample'],'item_type'=>$single['item_type'],'created_at'=>Carbon::now()];
			}

			if($createdVisit->visitdetails()->count())
					$createdVisit->visitdetails()->delete();
					
			VisitDetails::insert($items);
			 	\DB::commit();
		 } catch (\Exception $e) {
					\DB::rollback();
	   }	

	}


}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}