<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Repository\Interfaces\VisitInterface;
use App\Repository\VisitScheduleRepository;
use App\Models\Plan;
use App\Models\User;
use App\Http\Requests\API\VisitRequest;
use App\Http\Exports\DoctorVisitExport;
use Maatwebsite\Excel\Facades\Excel;


class VisitsController extends Controller
{
	public $IVisit;
    public function __construct(VisitInterface $IVisit)
    {
        $this->IVisit = $IVisit;
    }

	 public function index(Request $request){

		$response = $this->IVisit->getvisitsByPlan($request);
		return $this->SendResponse($response);
	 }

	 public function show($id){

		$response = $this->IVisit->getvisitDtail($id);
		return $this->SendResponse($response);
	 }

	 public function VisitAsSchedule(Request $request){
		$scheduleResult =(new VisitScheduleRepository())->createSchedule($request);

		return $this->SendResponse(["status"=>true, "message"=>trans('messages.success'),'data'=>$scheduleResult]);
	 }

	 public function store(VisitRequest $request){
        
		$response = $this->IVisit->submitVisit($request);
		 return $this->SendResponse($response);
	 }

	 public function visitCharts(Request $request){
           $response = $this->IVisit->getVisitCharts($request);
		     return $this->SendResponse($response);
	  }

	  public function AllVisits(){
		     $response = $this->IVisit->getAllVisits();
		     return $this->SendResponse($response);
	  }

         public function currentVisits(){
                   $response = $this->IVisit->getCurrentVisits();
		   return $this->SendResponse($response);
         }

	  public function UserVisits(Request $request){
			 $response = $this->IVisit->getVisitsByUserId($request);
		     return $this->SendResponse($response);
	  }

	  public function getAllUserVisits(Request $request){
           $response = $this->IVisit->getAllVisitsByUserId($request);
		     return $this->SendResponse($response);
	  }


	  public function createUnplannedVisit(Request $request){
			 $response = $this->IVisit->createUnplannedVisit($request);
		     return $this->SendResponse($response);
	  }

         
        public function userVisitStatictics(Request $request){
		$response = $this->IVisit->getUserVisitStatictics($request);
                return $this->SendResponse($response);

	  }

        public function userVisitSalesStatictics(Request $request){
		$response = $this->IVisit->getUserVisitAndSalesStatictics($request);
		 return $this->SendResponse($response);
	  }


	   public function exportUserVisitsToExcel(Request $request)
		{
			$result = $this->IVisit->getUserVisitStatictics($request);

			$user = User::find($request->user_id);
			$repName = $user ? $user->name : 'Unknown Rep';

			$startDate = $request->start_date ?? now()->toDateString();
			$endDate = $request->end_date ?? now()->toDateString();
			$visitDate = "$startDate - $endDate";

			$title = "Visits for $repName on $visitDate";

			return Excel::download(
				new DoctorVisitExport($result['data']['by_account'], $title),
				'user-visits-by-account.xlsx'
			);
		}




}
