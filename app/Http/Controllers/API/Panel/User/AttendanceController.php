<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AttendanceRequest;
use Illuminate\Http\Request;
use App\Http\Resources\API\AttendanceResource;
use App\Repository\Interfaces\AttendanceInterface;

class AttendanceController extends Controller
{
    protected AttendanceInterface $attendanceRepo;

    public function __construct(AttendanceInterface $attendanceRepo)
    {
        $this->attendanceRepo = $attendanceRepo;
    }
//AttendanceRequest
      public function storeAttendance(Request $request)
    {
        $user = $request->user();

        $result = $this->attendanceRepo->storeAttendance($user, $request);

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

        $result = $this->attendanceRepo->getTodayAttendanceStatus($user);

        return $this->response_api(true, 'Attendance status', $result);
    }

    public function getAttendanceLog(Request $request)
    {
        $user = $request->user();

        $attendance = $this->attendanceRepo->attendanceLog($user, $request);

        $data = AttendanceResource::collection($attendance);

        return $this->response_api(true, trans('messages.success'), $data);
    }
}