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
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		$request->plan_id = ($request->plan_id)??User::getCurrentPlan()?->id;
		$plan =Plan::find($request->plan_id); 
		if(!$plan)
		  return ["status"=>true, "message"=>trans('messages.success'),'data'=>[]];

		if($plan && $plan->status == 0 && auth()->user()->position == 3)
		  return ["status"=>true, "message"=>trans('messages.plan_reviewed'),'data'=>[]];
		
		if($plan && $plan->status == 2 && auth()->user()->position == 3)
		  return ["status"=>true, "message"=>trans('messages.plan_rejected'),'data'=>[]];  

			$visits = $plan->visits()->join('accounts','accounts.id','=','visits.account_id')->leftjoin('customers','customers.id','=','visits.customer_id')->select('visits.*')->filter($request)->paginate($limit);
			$data = VisitsResource::collection($visits);
	    return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];		
	}

	 public function getvisitDtail($id){
		$visit = Visit::find($id);
		if(!$visit)
		      return ["status"=>false, "message"=>trans('messages.data_not_found')];

           $user = User::find($visit->user_id);  
	   $visitProductItem = $this->getVisitItemList($visit,0); //type -- products
	   $listOfProduct= $this->mergeDataById($this->getUserProducts($user),$visitProductItem);

           $visitleaveBehind = $this->getVisitItemList($visit,GiftTypeEnum::LeaveBehind);
	   $listOfLeaveBehind= $this->mergeDataById($this->getGifts(GiftTypeEnum::LeaveBehind),$visitleaveBehind);

	   $visitGifts = $this->getVisitItemList($visit,GiftTypeEnum::Gift);
	   $listOfGist= $this->mergeDataById($this->getGifts(GiftTypeEnum::Gift),$visitGifts);

	   $visitAdditionalFiles = $this->getVisitItemList($visit,GiftTypeEnum::AdditionalFiles);

	   $listOfAdditionalFiles= $this->mergeDataById($this->getUserProductFiles($user),$visitAdditionalFiles);
 
		$data=[
			"visit"=>new VisitsResource($visit),
			"products"=>VisitDetailResource::collection($listOfProduct),
			"leaveBehind"=>VisitDetailResource::collection($listOfLeaveBehind),
			"Gifts"=>VisitDetailResource::collection($listOfGist),
			"AdditionalFiles"=>VisitDetailResource::collection($listOfAdditionalFiles),
		];
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	 }

	 protected function getUserProducts(User $user){
		return $user->products()->selectRaw('products.id , products.name ,products.image as file ,0 as count_of_sample , 0 as checked , 0 as type,products.price')->get()->keyBy('id'); 
	 }


	/* protected function getUserProductFiles2(){
		return auth()->user()->products()->join('product_files','product_files.product_id','=','products.id')
		->selectRaw('product_files.id , product_files.file as name ,0 as count_of_sample , 0 as checked , 3 as type')->whereNULL('product_files.deleted_at')->get();
	}*/

        protected function getUserProductFiles(User $user){
		return $user->products()->whereHas('productfiles', function($q){
                           $q->whereNULL('product_files.deleted_at');
                  })->selectRaw('products.id ,SUBSTRING(products.name, 1, 20) as name ,0 as count_of_sample , 0 as checked , 3 as type')->get();
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
		                                     'customer_id'=>isset($request->doctor_id)?$request->doctor_id:0,'visit_date'=>$visit_date];			
	
           $data = array_merge($existData , ['type'=>1]);											 
		$createdVisit = Visit::updateOrCreate($existData,$data);
		return $this->getvisitDtail($createdVisit->id);
	}
	
	public function submitVisit($request){
        try{
            $visit = Visit::find($request->visit_id);
		    $visitAccount = $visit->account;
                    $doctor_id  =  isset($request->doctor_id) && is_numeric($request->doctor_id) && $request->doctor_id > 0 ? $request->doctor_id : $visit->customer_id ; 
                    $combine_with  =  isset($request->combine_with) && is_numeric($request->combine_with) && $request->combine_with > 0 ? $request->combine_with : 0;  
	               //$combine_with =  isset($request->combine_with) && !empty($request->combine_with) && $request->combine_with != null ? $request->combine_with : 0 ;  

                    //'customer_id'=>$doctor_id
		    $data=['actual_start_date'=>Carbon::now()->toDateTimeString(),'actual_end_date'=>Carbon::now()->toDateTimeString(), 'customer_id'=>$doctor_id ,'combine_with'=>$combine_with];
			
			
			/*$allow_distance = !empty($this->getSetting()) && !empty($this->getSetting()->allow_distance) ? $this->getSetting()->allow_distance : 500;
                         $distance  = $this->getDistance($visitAccount->lat??0,$visitAccount->lng??0 ,$request->current_location_lat ,$request->current_location_lng );
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

	
	public function getVisitCharts($request)
	{
		[$searchDate, $formatDate] = $this->prepareDateParams($request);

		$query = $this->DrawVisitStatistics();   

		if ($searchDate) {
			$this->applyDateFilter($query, $searchDate, $formatDate);
		}

		if ($request->filled('search')) {
			$query->where('users.name', 'like', '%' . $request->search . '%');
		}

		$charts = $query
			->selectRaw('users.id, users.name, COUNT(*) as visit_count')
			->where('visits.status', VisitStatusEnum::Visited)
			->groupBy('users.id', 'users.name')
			->orderByDesc('visit_count')
			->limit(3)
			->get();

		return [
			'status'  => true,
			'message' => trans('messages.success'),
			'data'    => $charts,
		];
	}


	private function prepareDateParams($request): array
	{
		$date = $request->filled('search_date')
        ? Carbon::parse($request->search_date)
        : now();

		$format = match ($request->input('date_format')) {
			'YYYY'         => 'Y',
			'YYYY-MM-DD'   => 'Y-m-d',
			default        => 'Y-m',    // يساوى YYYY-MM
		};

		return [$date, $format];
	}

	private function applyDateFilter($query, Carbon $date, string $format): void
	{
		switch ($format) {
			case 'Y-m':        // شهر كامل
				$query->whereYear ('visits.visit_date', $date->year)
					->whereMonth('visits.visit_date', $date->month);
				break;

			case 'Y':          // سنة كاملة
				$query->whereYear('visits.visit_date', $date->year);
				break;

			case 'Y-m-d':      // يوم محدد
				$query->whereDate('visits.visit_date', $date->toDateString());
				break;
		}
	}

	     public function getAllVisits()
		{
			$request = request();
			if ($request->filled('search_date')) {
				$search = Carbon::parse($request->search_date);
				$startDate = $search->copy()->startOfMonth()->toDateString(); // YYYY-MM-01
				$endDate   = $search->copy()->endOfMonth()->toDateString();   // YYYY-MM-DD
			} else {
				$startDate = now()->startOfMonth()->toDateString();
				$endDate   = now()->endOfMonth()->toDateString();
			}
            $limit = (is_numeric(request()->get('per_page')) && (request()->get('per_page')) > 0) ? request()->get('per_page') : 20;


			$visits = $this->DrawVisitStatistics()
				->whereBetween('visits.visit_date', [$startDate, $endDate])
				->groupBy('users.id', 'users.name')       
				->orderByDesc('visit_count')              
				->paginate($limit);

			return [
				'status'  => true,
				'message' => trans('messages.success'),
				'data'    => VisitStatisticsResource::collection($visits),
			];
		}


		public function DrawVisitStatistics()
		{
			return Visit::query()
				->selectRaw("
					users.id,
					users.name,
					SUM(CASE WHEN visits.status = 2 THEN 1 ELSE 0 END)                                             AS visit_count,
					SUM(CASE WHEN visits.type   = 0 THEN 1 ELSE 0 END)                                             AS pln_visit_count,
					SUM(CASE WHEN visits.type   = 1 THEN 1 ELSE 0 END)                                             AS unpln_visit_count,
					SUM(CASE WHEN visits.status != 2 AND DATE(visits.visit_date) < CURDATE() THEN 1 ELSE 0 END)    AS missed_visit_count,
					SUM(CASE WHEN visits.status = 0  AND DATE(visits.visit_date) > CURDATE() THEN 1 ELSE 0 END)    AS pending_count
				")
				->join('users',  'users.id',  '=', 'visits.user_id')
				->join('plans',  'plans.id',  '=', 'visits.plan_id');
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
			$visits = $user->visits()->join('plans','plans.id','=','visits.plan_id')->join('accounts','accounts.id','=','visits.account_id')->leftjoin('customers','customers.id','=','visits.customer_id')->selectRaw('visits.*')->filter($request)->paginate($limit);
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

      public function getCurrentVisits(){

        $limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;

        $startDate = request()->get('start_date') && !empty(request()->get('start_date')) ? request()->get('start_date') : Carbon::today();
        $endDate = request()->get('end_date') && !empty(request()->get('end_date')) ? request()->get('end_date') : '';

        $visits = Visit::select('visits.*')->join('plans','plans.id','=','visits.plan_id')->whereHas('user', function ($query) {$query->where('users.status',1);})
         ->when($startDate ,fn($q,$v)=>$q->whereDate('visits.actual_start_date','>=',$v))
         ->when($endDate ,fn($q,$v)=>$q->whereDate('visits.actual_start_date','<=',$v))
        ->when(request()->get('user_id'),fn($q,$v)=>$q->where('visits.user_id', $v))
        ->where('visits.status',2)->orderBy('visits.created_at','DESC')->paginate($limit);

          $data = ["data"=> VisitsResource::collection($visits) ];
          return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data ];
      }

	    public function getUserVisitStatictics($request)
		{
			$startDate = $request->input('start_date');
			$endDate = $request->input('end_date');
			$userId = $request->input('user_id');

			// 1. Visits grouped by account
			$visits = Visit::join('accounts', 'visits.account_id', '=', 'accounts.id')
				->join('user_customers', function($join) use ($userId) {
					$join->on('user_customers.account_id', '=', 'accounts.id')
						->where('user_customers.user_id', '=', $userId);
				})
				->when($startDate, fn($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
				->when($endDate, fn($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
				->where('visits.user_id', $userId)
				->where('visits.status', 2)
				->select('accounts.name as account_name', \DB::raw('COUNT(DISTINCT visits.id) as total_visits'))
				->groupBy('accounts.name')
				->get();

			// 2. Visits grouped by specialty
			$bySpecialty = Visit::join('customers', 'visits.customer_id', '=', 'customers.id')
				->join('specialty', 'customers.specialty_id', '=', 'specialty.id')
				->when($startDate, fn($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
				->when($endDate, fn($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
				->where('visits.user_id', $userId)
				->where('visits.status', 2)
				->select('specialty.name as specialty_name', \DB::raw('COUNT(DISTINCT visits.id) as total_visits'))
				->groupBy('specialty.id')
				->get();

			// 3. Visits grouped by class
			$byClass = Visit::join('customers', 'visits.customer_id', '=', 'customers.id')
				->join('classes', 'customers.class_id', '=', 'classes.id')
				->when($startDate, fn($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
				->when($endDate, fn($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
				->where('visits.user_id', $userId)
				->where('visits.status', 2)
				->select('classes.name as class_name', \DB::raw('COUNT(DISTINCT visits.id) as total_visits'))
				->groupBy('classes.id')
				->get();

			return [
				"status" => true,
				"message" => trans('messages.success'),
				"data" => [
					'by_account' => $visits,
					'by_specialty' => $bySpecialty,
					'by_class' => $byClass,
				]
			];
		}


		public function getUserVisitAndSalesStatictics($request)
		{
			$startDate = $request->input('start_date') ?? now()->startOfMonth()->format('Y-m-d');
			$endDate = $request->input('end_date') ?? now()->endOfMonth()->format('Y-m-d');

			$userIds = (array) $request->input('user_id');

			$visits = Visit::join('accounts', 'visits.account_id', '=', 'accounts.id')
				->join('user_customers', function ($join) use ($userIds) {
					$join->on('user_customers.account_id', '=', 'accounts.id')
						->whereIn('user_customers.user_id', $userIds);
				})
				->leftJoin('sales', function ($join) use ($userIds, $startDate, $endDate) {
					$join->on('sales.account_id', '=', 'accounts.id')
						->whereIn('sales.user_id', $userIds)
						->whereDate('sales.month_date', '>=', $startDate)
						->whereDate('sales.month_date', '<=', $endDate);
				})
				->whereIn('visits.user_id', $userIds)
				->where('visits.status', 2)
				->whereDate('visits.visit_date', '>=', $startDate)
				->whereDate('visits.visit_date', '<=', $endDate)
				->select(
					'accounts.name as account_name',
					\DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
					\DB::raw('COALESCE(SUM(sales.total_price), 0) as total_sales')
				)
				->groupBy('accounts.name')
				->get();

			return [
				"status" => true,
				"message" => trans('messages.success'),
				"data" => [
					'by_account' => $visits,
				]
			];
		}







}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}