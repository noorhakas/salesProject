<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PublicHolidayRequest;
use App\Repository\Interfaces\AttendanceReportInterface;

class PublicHolidayController extends Controller
{
     public function __construct(
        protected AttendanceReportInterface $attendanceReportRepository
    ) {
    }

	public function index(Request $request)
	{
	    $response = $this->attendanceReportRepository->getPublicHoliday($request);
		return $this->SendResponse($response);
	}

	public function store(PublicHolidayRequest $request)
    {
		
		$response = $this->attendanceReportRepository->createPublicHoliday($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		
		$response = $this->attendanceReportRepository->showPublicHoliday($id);
		return $this->SendResponse($response);
    }

	public function update(PublicHolidayRequest $request,$id) {
		
		$response = $this->attendanceReportRepository->updatePublicHoliday($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		
		$response = $this->attendanceReportRepository->deletePublicHoliday($id);
		return $this->SendResponse($response);
	 
    }


}