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
use App\Models\Setting;
use App\Http\Resources\API\VisitDetailResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\VisitStatisticsResource;
use App\Enums\GiftTypeEnum;
use App\Enums\VisitStatusEnum;
use App\Models\VisitDetails;
use App\Models\Notification;

class VisitRepository implements VisitInterface
{
    public function getvisitsByPlan($request){
		$limit = (is_numeric($request->per_page) && ($request->per_page) > 0) ? $request->per_page : 20;
		$request->plan_id = ($request->plan_id)??User::getCurrentPlan()?->id;
		$plan =Plan::find($request->plan_id); 
		if(!$plan)
		  return ["status"=>true, "message"=>trans('messages.success'),'data'=>[]];

		if($plan && $plan->status == 0 && auth()->user()->position == 3)
		  return ["status"=>true, "message"=>trans('messages.plan_reviewed'),'data'=>[]];
		
		if($plan && $plan->status == 2 && auth()->user()->position == 3)
		  return ["status"=>true, "message"=>trans('messages.plan_rejected'),'data'=>[]];  

			$visits = $plan->visits()->join('customers','customers.id','=','visits.customer_id')->select('visits.*')->filter($request)->paginate($limit);
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

	  // dd($this->getUserProductFiles());
	   $listOfAdditionalFiles= $this->mergeDataById($this->getUserProductFiles(),$visitAdditionalFiles);

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
		return auth()->user()->products()->selectRaw('products.id , products.name ,0 as count_of_sample , 0 as checked , 0 as type,products.price')->get();
	 }


	 protected function getUserProductFiles(){
		return auth()->user()->products()->join('product_files','product_files.product_id','=','products.id')
		->selectRaw('product_files.id , product_files.file as name ,0 as count_of_sample , 0 as checked , 3 as type')->get();
	}

	 protected function getGifts($type = GiftTypeEnum::Gift){
		return Gift::selectRaw('id , name ,0 as count_of_sample , 0 as checked ,type')->where('type',$type)->get();
	 }

	 protected function getVisitItemList(Visit $visit ,$type = 0){
		return $visit->visitdetails()->selectRaw('item_id as id ,count_of_sample, 1 as checked ')->where('item_type',$type)->get()->keyBy('id');
	 
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


    public function createUnplannedVisit($request){

		 $currentPlanId = User::getCurrentPlan()?->id;
		 $visit_date = Carbon::now()->toDateString();
		 $existData =['plan_id'=>$currentPlanId ,'user_id'=>auth()->user()->id,'account_id'=>$request->account_id,
		                                     'customer_id'=>$request->doctor_id,'visit_date'=>$visit_date];			
	
           $data = array_merge($existData , ['type'=>1]);											 
		$createdVisit = Visit::updateOrCreate($existData,$data);
		return $this->getvisitDtail($createdVisit->id);
	}
	
	public function submitVisit($request){
        try{
            $visit = Visit::find($request->visit_id);
		    $visitAccount = $visit->account;	 
		    $data=['actual_start_date'=>$request->start_time,'actual_end_date'=>$request->end_time];
			
			$distance  = $this->getDistance($visitAccount->lat??0,$visitAccount->lng??0 ,$request->current_location_lat ,$request->current_location_lng );
			/*$allow_distance = !empty($this->getSetting()) && !empty($this->getSetting()->allow_distance) ? $this->getSetting()->allow_distance : 500;
			if ($distance > $allow_distance) {
				return ["status"=>false ,"message"=>trans('messages.wrong_place')];
			}*/

           if($visit->type == 1){ //unplanned visit
                $visit_date = Carbon::parse($request->start_time)->toDateString();
				$start_time = ($request->start_time) ? Carbon::parse($request->start_time)->format("H:i:s") : Carbon::now()->format("H:i:s") ;
				$end_time =   ($request->end_time) ? Carbon::parse($request->end_time)->format("H:i:s") : Carbon::now()->format("H:i:s") ;
		        $data = array_merge($data,['visit_date'=>$visit_date,'start_time'=>$start_time,'end_time'=>$end_time]);
			}
            
			$status =  (VisitStatusEnum::Visited)["id"];  
		    $data = array_merge(['status'=>$status , 'user_location_lat'=>$request->current_location_lat ,'user_location_lng'=>$request->current_location_lng ,'notes'=>$request->notes],$data);
			$createdVisit = Visit::updateOrCreate(['id'=>$visit->id],$data);
			$items = [];
			foreach($request->items as $i=>$single)
			{
				$items[] = ['visit_id'=>$createdVisit->id,'item_id'=>$single['item_id'] ,'count_of_sample'=>$single['sample'],'item_type'=>$single['item_type'],'created_at'=>Carbon::now()];
			}

			if($createdVisit->visitdetails()->count())
					$createdVisit->visitdetails()->delete();
					
			VisitDetails::insert($items);
			(new Notification)->sendNotification(['tokens'=>getUserFcmTokens(),'notify_title'=>'new_visit',
										'notify_body'=>'created_success_visit',
										'title' => __('messages.new_visit'),
										'msg' => __('messages.created_success_visit', ['vName' => auth()->user()->name]),
										'notify_userId'=>0,'model_type'=>'visit',
										'tiDeviceType'=>1,'notify_type'=>1,
										'model_id'=>$createdVisit->id]);	

                 return ["status"=>true ,"message"=>trans('messages.visit_success')];
		  }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
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

	  protected function getSetting(){
		return Setting::first();
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
			        sum(if(visits.status = 2, 1,0)) as visit_count,
					sum(if(visits.type = 0 , 1,0)) as pln_visit_count,
			        sum(if(visits.type = 1 , 1,0)) as unpln_visit_count, 
                    sum(if(visits.status = 0 and DATE(visits.visit_date) < CURDATE() , 1,0)) as missed_visit_count,
			        sum(if(visits.status = 0  and DATE(visits.visit_date) > CURDATE(), 1,0)) as pending_count,users.name , users.id')->join('users','users.id','=','visits.user_id')
					->join('plans','plans.id','=','visits.plan_id');
	  }

	  public function DrawVisitCountStatistics(){

		return  Visit::selectRaw('visits.account_id , count(visits.id) as visit_count')->join('users','users.id','=','visits.user_id')->join('plans','plans.id','=','visits.plan_id');
	  }

	  public function getVisitsByUserId($request){ //monthly
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


	  public function getAllVisitsByUserId($request){ //monthly
        $userId = $request->userId ?? auth()->user()->id ;
		$user = User::find($userId);
		if(!$user)
			   return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   	$limit = (is_numeric($request->per_page) && ($request->per_page) > 0) ? $request->per_page : 20;
			$visits = $user->visits()->join('plans','plans.id','=','visits.plan_id')->filter($request)->paginate($limit);

		  $data = ["data"=> VisitsResource::collection($visits) ];
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