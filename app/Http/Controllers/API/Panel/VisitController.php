<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Repository\VisitScheduleRepository;
use Carbon\Carbon;
use App\Models\Visit;
use App\Models\User;


class VisitController extends Controller
{
	public function visits(Request $request)
	{
		$request_date = isset($request->date) && !empty($request->date) ?Carbon::parse($request->date)->format("m") : Carbon::now()->format("m") ;
		$days = Carbon::now()->month($request_date)->daysInMonth; 
        $firstDay = Carbon::now()->month($request_date)->firstOfMonth()->toDateString();  
          
		$customers = $request->user()->customers()->selectRaw('customers.name,IFNULL(visits.status, 0) as status,IFNULL(visits.visit_date, 0) as visit_date')
						->leftJoin('visits', 'visits.user_id', 'user_customers.user_id')
						->get();
						
		$dates = $this->drawDates(['days'=>$days , 'firstDay' =>$firstDay]);
		$singleItem = collect();
		$all_visits =[];
		 foreach($customers as $i=>$item){
              $all_visits[$i]['name'] = $item->name;
			  if(!empty($item->visit_date))
                  $singleItem[$item->visit_date] = ["visit_date"=>$item->visit_date,"status"=>$item->status];

                  $result = $this->drawResult(['dates'=>$dates['dates'] ,'singleItem' =>$singleItem]); 
              $all_visits[$i]['dates'] = $result;	
		}

		$response=['dates' => $dates['dateString'] ,'plans'=>$all_visits];
		return $this->response_api(true,trans('messages.success'),$response);
	}


	public function CreateVisitSchedule(Request $request ,VisitScheduleRepository $scheduleRepository ){
		$request_date = isset($request->date) && !empty($request->date) ?Carbon::parse($request->date)->format("m") : Carbon::now()->format("m") ;
		$days = Carbon::now()->month($request_date)->daysInMonth; 
        $firstDay  = Carbon::now()->month($request_date)->firstOfMonth()->toDateString(); 
        $user= isset($request->user_id) ? User::find($request->user_id) :  auth()->user();
    

	     return $scheduleRepository->createSchedule(["month"=>$request_date ,'days'=>$days ,'firstDay'=>$firstDay]);


	}
	public function drawDates(array $arr){
		$dates = collect();
		$date_arr =[];
		for($i = 0;$i<$arr['days'] ;$i++){
			$date = Carbon::parse($arr['firstDay'])->addDays($i)->toDateString();
			$date_arr[] = $date;
			$dates[$date] = ["status"=>0,"visit_date"=>$date];
		}
		return ["dateString"=>$date_arr ,'dates'=>$dates ];
	}

	public function drawResult(array $data){
		$result=[];
		foreach($this->mergeDataByDate($data['singleItem'],$data['dates']) as $item){
		  $result []= [
			  'visit_date'            => $item->visit_date,
			  'status'         => $item->status
		  ]; 
		}
		return $result;
	}

	public function mergeDataByDate(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $date => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                };
				$data[] = ReportData::make(array_merge(isset($data[$date]) ? $data[$date]->toArray() : ['date' => $date], $item->toArray()));
            }
        }
        return collect($data)->sortBy('date', SORT_REGULAR, false);
    }
}
class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}