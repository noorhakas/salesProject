<?php

namespace App\Http\Controllers\API\Panel\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\VisitInterface;
use App\Repository\VisitScheduleRepository;
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

	 public function store(VisitRequest $request){
        
		$response = $this->IVisit->submitVisit($request);
		 return $this->SendResponse($response);
	 }

	
    public function currentVisits(){
        $response = $this->IVisit->getCurrentVisits();
		   return $this->SendResponse($response);
    }


	public function createUnplannedVisit(Request $request){
			 $response = $this->IVisit->createUnplannedVisit($request);
		     return $this->SendResponse($response);
	  }

     



}
