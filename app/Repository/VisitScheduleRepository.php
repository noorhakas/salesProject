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
		for($i = 0;$i<$arr['days'] ;$i++){
			$dateObj = Carbon::parse($arr['firstDay'])->addDays($i);
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


	public function getUserVisitsByDate($request){

		$limit = $request->per_page ?? 20;
		$user= isset($request->user_id) && !empty($request->user_id) ? User::find($request->user_id) : auth()->user();
		$request->visit_date = isset($request->date) && !empty($request->date) ? Carbon::now()->today()->toDateString() : Carbon::parse($request->date)->toDateString();
      
		$uservisits = $user->visits()->filter($request)->paginate($limit);
		  return VisitsResource::collection($uservisits);
	}

	public function getAllVisits($request){

		$limit = $request->per_page ?? 20;
		$request->visit_date = isset($request->date) && !empty($request->date) ? Carbon::now()->today()->toDateString() : Carbon::parse($request->date)->toDateString();

           $all_Visits = Visit::join('users', 'visits.user_id', '=', 'users.id')
							->join('customers', 'visits.customer_id', '=', 'customers.id')
							->filter($request)
							->select('visits.*')->paginate($limit);
		   return VisitsResource::collection($all_Visits);
	}


	public function submitPannedOrUnplannedVisit(array $data){
		
		   
        try {
			
			\DB::beginTransaction();
			$customer = Customer::find($data['customer_id']);
		    $user_location = $data['current_location']; $message = '';

			$distance  = $this->getDistance($customer->lat,$customer->lng ,$user_location?$user_location[0] :'' ,$user_location?$user_location[1]:'' );
			if ($distance < 100) {
				$status =  (VisitStatusEnum::Visited)["id"];
				$message = ["message"=>trans('messages.visit_success'),"status"=>true]; //'visit saved successfuly';
			} else {
				$status =  (VisitStatusEnum::Fault_Visit)["id"];
				$message = ["message"=>trans('messages.visit_false'),"status"=>false];
			}

			$data = array_merge(['status'=>$status , 'user_location'=>$user_location ,'acc_type_id'=>$data['account_id']],$data);
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
	 return $message;
		 } catch (\Exception $e) {
				\DB::rollback();
	   }	

	}



	function getDistance($latitude1, $longitude1, $latitude2, $longitude2) {  
		$earth_radius = 6371;
	  
		$dLat = deg2rad($latitude2 - $latitude1);  
		$dLon = deg2rad($longitude2 - $longitude1);  
	  
		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);  
		$c = 2 * asin(sqrt($a));  
		$d = $earth_radius * $c;  
	  
		return $d;  
	  }

}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}