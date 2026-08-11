<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AttendanceRequest;
use App\Http\Resources\API\AttendanceResource;
use App\Repository\Interfaces\AttendanceInterface;
use App\Http\Resources\API\UserAttendanceResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected AttendanceInterface $attendanceRepo;

    public function __construct(AttendanceInterface $attendanceRepo)
    {
        $this->attendanceRepo = $attendanceRepo;
    }

    public function index(Request $request)
    {
        $manager = $request->user();

        $date = Carbon::parse($request->date ?? today());
        $position = $request->input('position');

        $query = User::query()->with('userposition')->where('users.status',1);

        if ($position == 'sales_rep') {
            $query->whereIn('users.id', $manager->getAllSubordinateIds());
        } elseif ($position == 'supervisor') {
            $query->where('manager_id', $manager->id);
        } else {
            // لو مفيش position
            $query->where(function ($q) use ($manager) {
                $q->whereIn('users.id', $manager->getAllSubordinateIds())
                ->orWhere('manager_id', $manager->id);
            });
        }

        $query->when($position, function ($q) use ($position) {
            $q->whereHas('userposition', function ($sub) use ($position) {
                $sub->where('ps_key', $position);
            });
        });

    

        $statistics = app(AttendanceStatusService::class)->statistics(clone $query, $date);

        $employees = app(AttendanceStatusService::class)->list(clone $query, $date, $request);

        return $this->response_api(true, trans('messages.success'), [
            'statistics' => $statistics,
            'employees'  => UserAttendanceResource::collection($employees),
        ]);
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