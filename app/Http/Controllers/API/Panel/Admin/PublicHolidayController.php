<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PositionRequest;
use App\Repository\Interfaces\AttendanceReportInterface;

class PublicHolidayController extends Controller
{
     public function __construct(
        protected AttendanceReportInterface $attendanceReportRepository
    ) {
    }

	public function index(Request $request)
	{
		//if (!auth()->user()->hasPermissionTo('display Acc-Type'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->attendanceReportRepository->getPublicHoliday($request);
		return $this->SendResponse($response);
	}

	public function store(PositionRequest $request)
    {
		//if (!auth()->user()->hasPermissionTo('create Acc-Type'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->attendanceReportRepository->createPublicHoliday($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		//if (!auth()->user()->hasPermissionTo('display Acc-Type'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->attendanceReportRepository->showPublicHoliday($id);
		return $this->SendResponse($response);
    }

	public function update(PositionRequest $request,$id) {
		//if (!auth()->user()->hasPermissionTo('update Acc-Type'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->attendanceReportRepository->updatePublicHoliday($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		//if (!auth()->user()->hasPermissionTo('delete Acc-Type'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->attendanceReportRepository->deletePublicHoliday($id);
		return $this->SendResponse($response);
	 
    }


}