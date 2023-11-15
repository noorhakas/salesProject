<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\VisitInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Models\Gift;
use App\Models\Account;
use App\Http\Resources\API\VisitDetailResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\VisitStatisticsResource;
use App\Enums\GiftTypeEnum;
use App\Enums\VisitStatusEnum;
use App\Models\VisitDetails;

class VisitRepository implements VisitInterface
{
    public function getvisitsByPlan($request){
		$limit = (is_numeric($request->per_page) && ($request->per_page) > 0) ? $request->per_page : 20;
		$request->plan_id = ($request->plan_id)??User::getCurrentPlan()?->id;
		$plan =Plan::find($request->plan_id); 
			$visits = $plan->visits()->filter($request)->paginate($limit);

			$data = VisitsResource::collection($visits);
	    return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];		
	}

	 public function getvisitDtail($id){
		$visit = Visit::find($id);
		if(!$visit)
		      return ["status"=>false, "message"=>trans('messages.data_not_found')];

		$visitProductItem = $this->getVisitItemList($visit,0); //type -- products
		$listOfProduct= $this->mergeDataById($this->getUserProducts(),$visitProductItem);

       $visitleaveBehind = $this->getVisitItemList($visit,GiftTypeEnum::LeaveBehind);
	   $listOfLeaveBehind= $this->mergeDataById($this->getGifts(GiftTypeEnum::LeaveBehind),$visitleaveBehind);

	   $visitGifts = $this->getVisitItemList($visit,GiftTypeEnum::Gift);
	   $listOfGist= $this->mergeDataById($this->getGifts(GiftTypeEnum::Gift),$visitGifts);

	   $visitAdditionalFiles = $this->getVisitItemList($visit,GiftTypeEnum::AdditionalFiles);
	   $listOfAdditionalFiles= $this->mergeDataById($this->getGifts(GiftTypeEnum::AdditionalFiles),$visitAdditionalFiles);

		$data=[
			"visit"=>new VisitsResource($visit),
			"products"=>VisitDetailResource::collection($listOfProduct),
			"leaveBehind"=>VisitDetailResource::collection($listOfLeaveBehind),
			"Gifts"=>VisitDetailResource::collection($listOfGist),
			"AdditionalFiles"=>VisitDetailResource::collection($listOfAdditionalFiles),
		];
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	 }

	 protected function getUserProducts(){
		return auth()->user()->products()->selectRaw('products.id , products.name ,0 as count_of_sample , 0 as checked , 0 as type')->get(['products.id','products.name']);
	 }

	 protected function getGifts($type = GiftTypeEnum::Gift){
		return Gift::selectRaw('id , name ,0 as count_of_sample , 0 as checked ,type')->where('type',$type)->get();
	 }

	 protected function getVisitItemList(Visit $visit ,$type = 0){
		switch($type){
			case 0:
				return $visit->visitdetailProducts()->selectRaw('item_id as id ,count_of_sample, 1 as checked ')->get()->keyBy('id');
			break;
			default:
			   return $visit->visitdetailGifts()->selectRaw('item_id as id ,count_of_sample, 1 as checked ')->where('item_type',$type)->get()->keyBy('id');
			break;
		}
         
	 }
	 public function mergeDataById(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $Id => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                };
				$data[$Id] = ReportData::make(array_merge(isset($data[$Id]) ? $data[$Id]->toArray() : ['id' => $Id], $item->toArray()));
            }
        }
        return collect($data)->sortBy('id', SORT_REGULAR, false)->values();
    }

	public function mergeDataByAccountId(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $Id => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                };
				$data[$Id] = ReportData::make(array_merge(isset($data[$Id]) ? $data[$Id]->toArray() : ['account_id' => $Id], $item->toArray()));
            }
        }
        return collect($data)->sortBy('account_id', SORT_REGULAR, false)->values();
    }



	
	public function submitVisit($request){
  

        if(isset($request->visit_id) && !empty($request->visit_id)) //planned;
        {   $visit = Visit::find($request->visit_id);
			if(!$visit)
			  return ["status"=>false, "message"=>trans('messages.data_not_found')];
      
		   $visitAccount = $visit->account;	  
           $data=[];
		   $existData =['id'=>$request->visit_id];
		   $type = 0; 
		}else{      //unplanned;
           $visitAccount = Account::find($request->account_id);
		   if(!$visitAccount)
			  return ["status"=>false, "message"=>trans('messages.data_not_found')];
		   $currentPlanId = User::getCurrentPlan()?->id;
		   $type = 1; //Unplanned;
		   $existData =['plan_id'=>$currentPlanId ,'user_id'=>auth()->user()->id,'account_id'=>$visitAccount->id,
		                'customer_id'=>$request->doctor_id,'visit_date'=>$request->visit_date];

		   $data=['plan_id'=>$currentPlanId ,'user_id'=>auth()->user()->id,'account_id'=>$visitAccount->id,'type'=>1,
		        'customer_id'=>$request->doctor_id,'visit_date'=>$request->visit_date,'start_time'=>$request->start_time,'end_time'=>$request->end_time];				
	
		}
      
		$distance  = $this->getDistance($visitAccount->lat??0,$visitAccount->lng??0 ,$request->current_location_lat ,$request->current_location_lng );
		if ($distance < 100) {
			$status =  (VisitStatusEnum::Visited)["id"];
			$message = ["status"=>true ,"message"=>trans('messages.visit_success')]; //'visit saved successfuly';
		} else {
			$status =  (VisitStatusEnum::False_Visit)["id"];
			$message = ["status"=>false,"message"=>trans('messages.visit_false')];
		}

		    $data = array_merge(['status'=>$status , 'user_location_lat'=>$request->current_location_lat ,'user_location_lng'=>$request->current_location_lng ,'notes'=>$request->notes],$data);
			$createdVisit = Visit::updateOrCreate($existData,$data);
			$items = [];
			foreach($request->items as $i=>$single)
			{
				$items[] = ['visit_id'=>$createdVisit->id,'item_id'=>$single['item_id'] ,'count_of_sample'=>$single['sample'],'item_type'=>$single['item_type'],'created_at'=>Carbon::now()];
			}

			if($createdVisit->visitdetails()->count())
					$createdVisit->visitdetails()->delete();
					
			VisitDetails::insert($items);

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

	  public function getVisitCharts($request){
			$startDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-01') : Carbon::now()->startOfMonth()->toDateString();
			$endDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-t') : Carbon::now()->endOfMonth()->toDateString();

			$charts = (clone $this->DrawVisitStatistics())->whereDate('visits.visit_date','>=',$startDate)->whereDate('visits.visit_date','<=',$endDate)->where('visits.status',VisitStatusEnum::Visited)
						  ->orderBy('visit_count','desc')->groupBy('users.id')->take(10)->get();

			return ["status"=>true, "message"=>trans('messages.success'),'data'=>$charts];
	  }

	  public function getAllVisits(){

            $startDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-01') : Carbon::now()->startOfMonth()->toDateString();
			$endDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-t') : Carbon::now()->endOfMonth()->toDateString();

		    $limit = (is_numeric(request()->get('per_page')) && (request()->get('per_page')) > 0) ? request()->get('per_page') : 20;
			$visits = (clone $this->DrawVisitStatistics())->whereDate('visits.visit_date','>=',$startDate)->whereDate('visits.visit_date','<=',$endDate)
						 ->groupBy('users.id')->paginate($limit);

		          $data =VisitStatisticsResource::collection($visits);			 

			return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }
	  
	  public function DrawVisitStatistics(){

		return  Visit::selectRaw('
			        sum(if(visits.status != 0, 1,0)) as visit_count,
					sum(if(visits.status = 2 and visits.type = 0 , 1,0)) as pln_visit_count,
			        sum(if(visits.status = 2 and visits.type = 1 , 1,0)) as unpln_visit_count, 
					sum(if(visits.status = 4 , 1,0)) as false_visit_count,  
                    sum(if(visits.status = 0 and DATE(visits.visit_date) < CURDATE() , 1,0)) as missed_visit_count,
			        sum(if(visits.status = 0  and DATE(visits.visit_date) > CURDATE(), 1,0)) as pending_count,users.name , users.id')->join('users','users.id','=','visits.user_id');
	  }

	  public function DrawVisitCountStatistics(){

		return  Visit::selectRaw('visits.account_id , count(visits.id) as visit_count')->join('users','users.id','=','visits.user_id');
	  }

	  public function getVisitsByUserId($request){
        $userId = $request->userId ?? auth()->user()->id ;
		$startDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-01') : Carbon::now()->startOfMonth()->toDateString();
		$endDate =isset($request->search_date) && !empty($request->search_date) ? Carbon::parse($request->search_date)->format('Y-m-t') : Carbon::now()->endOfMonth()->toDateString();

		$user = User::find($userId);
		if(!$user)
			   return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   	$limit = (is_numeric($request->per_page) && ($request->per_page) > 0) ? $request->per_page : 20;
			$visits = $user->visits()->filter($request)->whereDate('visits.visit_date','>=',$startDate)->whereDate('visits.visit_date','<=',$endDate)->paginate($limit);
		    $visitStatistics = (clone $this->DrawVisitStatistics())->whereDate('visits.visit_date','>=',$startDate)->whereDate('visits.visit_date','<=',$endDate)->where('users.id',$userId)->groupBy('users.id')->first();
			
		  $data = ["visit_statistics"=>new VisitStatisticsResource($visitStatistics),"data"=> VisitsResource::collection($visits),"user"=>new UserResource($user),"currentDate"=>$startDate ];
          return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];

	  }


}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}