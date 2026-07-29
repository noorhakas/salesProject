<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AttendanceRequest;
use Illuminate\Http\Request;
use App\Repository\Interfaces\AttendanceInterface;

class AttendanceController extends Controller
{
    protected AttendanceInterface $attendance;

    public function __construct(AttendanceInterface $attendance)
    {
        $this->attendance = $attendance;
    }
//AttendanceRequest
      public function storeAttendance(Request $request)
    {
        $user = $request->user();

        $result = $this->attendance->storeAttendance($user, $request);

        return match ($result) {
            'already_checked_in'   => $this->response_api(false, __('attendance.already_checked_in')),
            'must_check_in_first'  => $this->response_api(false, __('attendance.must_check_in_first')),
            'already_checked_out'  => $this->response_api(false, __('attendance.already_checked_out')),
            'device_not_allowed'   => $this->response_api(false, __('attendance.device_not_allowed')),
            'distance_not_allowed' => $this->response_api(false, __('attendance.distance_not_allowed')),
            'biometric_locked'     => $this->response_api(false, __('attendance.biometric_locked')),
            'failed'               => $this->response_api(false, __('attendance.failed')),
            'success'              => $this->response_api(true, __('attendance.success')),
            default                => $this->response_api(false, __('attendance.something_went_wrong')),
        };
    }

    public function todayAttendanceStatus(Request $request)
    {
        $user = $request->user();

        $result = $this->attendance->getTodayAttendanceStatus($user);

        return $this->response_api(true, 'Attendance status', $result);
    }
    public function index(\Illuminate\Http\Request $request)
    {
        $response = $this->attendance->getUserAttendance($request, auth()->id());
        return $this->SendResponse($response);
    }
}